/**
 * Run with `gcc -g -pedantic -std=c99 -Wall -Wextra relative_value.c -o calcrv && ./a.out`
 */

#define TARGET_RECORDS "SELECT DISTINCT grouping, meter_uuid FROM relative_values WHERE grouping != '[]' AND grouping != '' AND grouping IS NOT NULL AND permission IS NOT NULL"
#define SMALL_CONTAINER 255

#include <stdio.h>
#include <mysql.h>
#include "./lib/cJSON/cJSON.h"
#include "db.h"

int main(int argc, char const *argv[]) {
	char query[SMALL_CONTAINER];
	sprintf(query, TARGET_RECORDS, meter_id);
	if (mysql_query(conn, query)) {
		fprintf(stderr, "%s\n", mysql_error(conn));
		exit(1);
	}
	res = mysql_store_result(conn);
	while ((row = mysql_fetch_row(res))) {
		printf("%s %s\n", row[0], row[1]);
	}
	mysql_free_result(res);
	return 0;
}
