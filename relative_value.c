/**
 * Updates the relative_values table
 */
#define _XOPEN_SOURCE // for strptime
#define _GNU_SOURCE // for strptime
#define TARGET_RECORDS "SELECT DISTINCT grouping, meter_uuid FROM relative_values WHERE grouping != '[]' AND grouping != '' AND grouping IS NOT NULL AND permission IS NOT NULL GROUP BY meter_uuid, grouping ORDER BY AVG(last_updated) ASC"
#define CURRENT_READING1 "SELECT AVG(value) FROM meter_data WHERE meter_id = %d AND resolution = 'live' AND recorded >= %d AND value IS NOT NULL"
#define CURRENT_READING2 "SELECT current FROM meters WHERE id = %d"
#define TYPICAL_DATA1 "SELECT value FROM meter_data WHERE meter_id = %d AND value IS NOT NULL AND resolution = '%s' AND HOUR(FROM_UNIXTIME(recorded)) = HOUR(NOW()) AND DAYOFWEEK(FROM_UNIXTIME(recorded)) IN (%s) ORDER BY recorded DESC LIMIT %d"
#define TYPICAL_DATA2 "SELECT value FROM meter_data WHERE meter_id = %d AND value IS NOT NULL AND recorded > %d AND recorded < %d AND resolution = '%s' AND HOUR(FROM_UNIXTIME(recorded)) = HOUR(NOW()) AND DAYOFWEEK(FROM_UNIXTIME(recorded)) IN (%s) ORDER BY value ASC"
#define ISO8601_FORMAT "%Y-%m-%dT%H:%M:%S-04:00" // EST is -4:00
#define SMALL_CONTAINER 255

#include <stdio.h>
#include <string.h>
#include <mysql.h>
#include <stdlib.h>
#include <time.h>
#include "libc/cJSON/cJSON.h"
#include "../daemons/db.h"


/**
 * Scale a percent (value which is 0-100) to a new min and max
 * @param  pct value to scale
 * @param  min new min of range
 * @param  max new max of range
 */
float scale(float pct, int min, int max) {
	return (pct / 100.0) * (max - min) + min;
}

/**
 * comporator for qsort
 */
int compare(const void *a, const void *b) {
  float fa = *(const float*) a;
  float fb = *(const float*) b;
  return (fa > fb) - (fa < fb);
}

/**
 * Produces the relative value for a data set given in an array of 'typical' and a 'current' to
 * compare against
 */
float relative_value(float *typical, float current, int size, int min, int max) {
	int i, j, k;
	k = 0;
	qsort(typical, size, sizeof(float), compare);
	for (i = 0; i < size; ++i) {
		if (typical[i] >= current) {
			j = i;
			while (j < size && current == typical[++j])
				k++;
			break;
		}
	}
	float rv = ((i - k) / (size - 1)) * 100;
	return scale(rv, min, max);
}

void update_meter_rv(MYSQL *conn, char *grouping, char *uuid, int day_of_week, time_t t) {
	MYSQL_RES *res;
	MYSQL_ROW row;
	int target_days[8];
	float typical[SMALL_CONTAINER];
	char query[SMALL_CONTAINER];
	char day_sql_str[50];
	sprintf(query, "SELECT id FROM meters WHERE bos_uuid = '%s'", uuid);
	if (mysql_query(conn, query)) {
		fprintf(stderr, mysql_error(conn));
	}
	res = mysql_store_result(conn);
	row = mysql_fetch_row(res);
	mysql_free_result(res);
	int meter_id = atoi(row[0]);
	cJSON *root = cJSON_Parse(grouping);
	int k = 0;
	int typicali = 0;
	for (int i = 0; i < cJSON_GetArraySize(root); i++) {
		int this_iteration = 0;
		cJSON *subitem = cJSON_GetArrayItem(root, i);
		cJSON *days = cJSON_GetObjectItem(subitem, "days");
		cJSON *npoints = cJSON_GetObjectItem(subitem, "npoints");
		// printf("\nnpoints: %d\ndays: ", npoints->valueint);
		int num_days = cJSON_GetArraySize(days);
		for (int j = 0; j < num_days; j++) {
			int day_index = cJSON_GetArrayItem(days, j)->valueint;
			target_days[j] = day_index;
			day_sql_str[k++] = day_index + '0'; // https://stackoverflow.com/a/2279401/2624391
			if (j != num_days - 1) {
				day_sql_str[k++] = ',';
			} else {
				day_sql_str[k] = '\0';
			}
			if (day_of_week == day_index) {
				this_iteration = 1;
			}
		}
		if (this_iteration) { // target_days filled with days in same group as current day
			// Either calculate the current value based on the average of the last n minutes or the last non null point
			if (cJSON_HasObjectItem(subitem, "minsAveraged")) {
				int secsAveraged = cJSON_GetObjectItem(subitem, "minsAveraged")->valueint * 60;
				sprintf(query, CURRENT_READING1, meter_id, (int) t-secsAveraged);
			} else {
				sprintf(query, CURRENT_READING2, meter_id);
			}
			if (mysql_query(conn, query)) {
				fprintf(stderr, mysql_error(conn));
			}
			res = mysql_store_result(conn);
			row = mysql_fetch_row(res);
			mysql_free_result(res);
			if (row == NULL) {
				fprintf(stderr, "No results 1\n");
				exit(1);
			}
			float current = atof(row[0]);
			
			if (cJSON_HasObjectItem(subitem, "npoints")) {
				sprintf(query, TYPICAL_DATA1, meter_id, "hour", day_sql_str, cJSON_GetObjectItem(subitem, "npoints")->valueint);
			} else if (cJSON_HasObjectItem(subitem, "start")) {
				struct tm ltm = {0};
				time_t epoch = 0;
				if (strptime(cJSON_GetObjectItem(subitem, "start")->valuestring, ISO8601_FORMAT, &ltm) != NULL) {
					epoch = mktime(&ltm);
				} else {
					fprintf(stderr, "Unable to parse date");
					exit(1);
				}
				sprintf(query, TYPICAL_DATA2, meter_id, (int) epoch, (int) t, "hour", day_sql_str);
			} else {
				fprintf(stderr, "%s\n", "Error parsing relative value configuration");
				exit(1);
			}
			if (mysql_query(conn, query)) {
				fprintf(stderr, mysql_error(conn));
			}
			res = mysql_store_result(conn);
			row = mysql_fetch_row(res);
			if (row == NULL) {
				fprintf(stderr, "No results 2\n");
				exit(1);
			}
			while ((row = mysql_fetch_row(res))) {
				typical[typicali++] = atof(row[0]);
			}
			mysql_free_result(res);
			float rv = relative_value(typical, current, typicali, 0, 100);
			sprintf(query, "UPDATE relative_values SET relative_value = %f WHERE meter_uuid = '%s' AND grouping = '%s'", rv, uuid, grouping);
			printf("%s\n", query);
			// if (mysql_query(conn, query)) {
			// 	fprintf(stderr, mysql_error(conn));
			// 	exit(1);
			// }
			break;
		}
	}
}

int main(void) {
	time_t t = time(NULL);
	int day_of_week = localtime(&t)->tm_wday + 1; // https://dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_dayofweek
	MYSQL *conn;
	conn = mysql_init(NULL);
	// Connect to database
	if (!mysql_real_connect(conn, DB_SERVER,
	DB_USER, DB_PASS, DB_NAME, 0, NULL, 0)) {
		fprintf(stderr, "%s\n", mysql_error(conn));
	}
	MYSQL_RES *res;
	MYSQL_ROW row;
	if (mysql_query(conn, TARGET_RECORDS)) {
		fprintf(stderr, "%s\n", mysql_error(conn));
		exit(1);
	}
	res = mysql_store_result(conn);
	while ((row = mysql_fetch_row(res))) {
		update_meter_rv(conn, row[0], row[1], day_of_week, t);
		// printf("%s %s\n", row[0], row[1]);
		break;
	}
	mysql_free_result(res);
	return 0;
}
