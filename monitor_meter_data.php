<?php
#!/usr/local/bin/php
error_reporting(-1);
set_time_limit(0);
ini_set('display_errors', 'On');
date_default_timezone_set('America/New_York');
chdir(__DIR__);
require '../includes/db.php'; // Has $db
$resolutions = array('live', 'quarterhour', 'hour', 'month');
// 'resolution'=>(seconds res is stored for - a bit of a buffer)
$data_lifespans = array('live'=>(7200-300), 'quarterhour'=>(1209600-1800), 'hour'=>(5184000-7200), 'month'=>(63113904-60480));
$time = time();
foreach ($db->query('SELECT id, url FROM meters WHERE source = \'buildingos\' AND ((gauges_using > 0 OR for_orb > 0 OR timeseries_using > 0) OR bos_uuid IN (SELECT DISTINCT meter_uuid FROM relative_values WHERE permission = \'orb_server\' AND meter_uuid != \'\')) AND id NOT IN (SELECT updating_meter FROM daemons WHERE target_res = \'live\')') as $meter) {
	foreach ($resolutions as $res) {
		$stmt = $db->prepare('SELECT recorded FROM meter_data WHERE meter_id = ? AND resolution = ? ORDER BY recorded ASC LIMIT 1');
		$stmt->execute(array($meter['id'], $res));
		if ($stmt->fetchColumn() > $time - $data_lifespans[$res]) {
			echo "must update meter {$meter['id']} ({$res})\n";
		}
	}
}
?>