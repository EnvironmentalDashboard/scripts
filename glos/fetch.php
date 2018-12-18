<?php
require '../../includes/db.php';
define('OFFSET_TIME', 978307200); // # of seconds between 1/1/1970 0:00 GMT and 1/1/2001 0:00 GMT
define('STORAGE_DURATION', 259200); // at most we store 3 days of "live" data
define('TARGET_BUOYS', ['leelyria', 'leavon', 'lementor', '45176']);
define('CACHE_FN', 'last_readings');
date_default_timezone_set('America/New_York');

$time = time();
$options = getopt('v:');
$verbose = (isset($options['v']) && $options['v'] === '1') ? true : false;
$buoys = array_fill_keys(TARGET_BUOYS, []);
$new_last_readings = array_fill_keys(TARGET_BUOYS, []);
$last_readings = (file_exists(CACHE_FN)) ? unserialize(file_get_contents(CACHE_FN)) : false;

foreach ($buoys as $buoy => &$meters) {
	$url = "http://tds.glos.us/thredds/dodsC/buoy_agg_standard/{$buoy}/{$buoy}.ncml.dds";
	foreach (explode("\n", file_get_contents($url)) as $line) { // iterate over meters in buoy
		$parts = explode(' ', $line);
		$count = count($parts);
		if ($count === 8) { // only lines with 8 spaces contain real variables
			$var_name = substr($parts[5], 0, -5);
			$meter_name = "{$var_name} (buoy {$buoy})";
			$stmt = $db->prepare('SELECT id FROM meters WHERE name = ? LIMIT 1');
			$stmt->execute([$meter_name]);
			$id = $stmt->fetchColumn();
			if ($id == false) { // create new meter; test probably equivalent to $last_readings === false
				$stmt = $db->prepare('INSERT INTO meters (building_id, source, scope, resource, name, calculated, units) VALUES (?, ?, ?, ?, ?, ?, ?)');
				$stmt->execute([1, 'glos', 'Other', 'Undefined', $meter_name, 1, '']);
				$id = $db->lastInsertId();
				if ($verbose) {
					echo "Creating new meter {$meter_name}\n";
				}
			}
			$cur_data_index = intval($parts[7]) - 1; // its 0-based but not returned like that from glos...
			$meters[] = [
				'var_name' => $var_name,
				'cur_reading' => $cur_data_index,
				'last_reading' => ($last_readings && isset($last_readings[$buoy][$var_name])) ? $last_readings[$buoy][$var_name] : 0,
				'id' => $id
			];
			$new_last_readings[$buoy][$var_name] = $cur_data_index;
		}
	}
	$params = [];
	foreach ($meters as $meter) { // go back over meters and actually fetch data
		if ($meter['last_reading'] < $meter['cur_reading']) {
			$params[] = "{$meter['var_name']}[{$meter['last_reading']}:1:{$meter['cur_reading']}]";
		}
	}
	if (empty($params)) { // none of the meters on this buoy need updating
		continue;
	}
	$url = "http://tds.glos.us/thredds/dodsC/buoy_agg_standard/{$buoy}/{$buoy}.ncml.ascii?" . rawurlencode(implode(',', $params));
	$cur_var_name = '';
	$cur_meter_id = 'ERROR';
	$times = [];
	foreach (explode("\n", explode("---------------------------------------------", file_get_contents($url))[1]) as $line) {
		if ($line === '') {
			continue;
		}
		if (strpos($line, '[') !== false) { // this line is a variable
			$cur_var_name = trim(explode('[', $line)[0]);
		} else {
			if ($cur_var_name === 'time') { // time is always as the top so it should be available
				$times = array_map('floatval', explode(',', $line));
				continue; // dont actually want to insert times into db
			}
			foreach (explode(',', $line) as $i => $value) {
				foreach ($meters as $meter) {
					if ($meter['var_name'] === $cur_var_name) {
						$cur_meter_id = $meter['id'];
						break;
					}
				}
				if (!isset($times[$i])) { // not sure how this could happen but if it does just skip this data point
					continue;
				}
				$cur_meter_id = intval($cur_meter_id);
				$value = floatval($value);
				$new_row = [$cur_meter_id,
										($value === -9999.0) ? null : $value, // -9999 is an error value
										$times[$i] + OFFSET_TIME,
										'live'];
				try {
					$stmt = $db->prepare('REPLACE INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)');
					$stmt->execute($new_row);
					if ($verbose) {
						echo "Inserting " . json_encode($new_row) . "\n";
					}
				} catch (PDOException $e) { // if value is out of range
					if ($verbose) {
						echo $e->getMessage() . ': ' . json_encode($new_row) . "\n";
					}
				}
			}
		}
	}
	
}

file_put_contents(CACHE_FN, serialize($new_last_readings));
