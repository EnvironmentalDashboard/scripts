<?php
#!/usr/local/bin/php
error_reporting(-1);
set_time_limit(0);
ini_set('display_errors', 'On');
date_default_timezone_set('America/New_York');
chdir(__DIR__);
require '../includes/db.php'; // Has $db
require '../includes/class.BuildingOS.php';
// $resolutions = array('live', 'quarterhour', 'hour', 'month');
$resolutions = array('quarterhour', 'hour');
// each entry is: 'resolution'=>(seconds res is stored for - a bit of a buffer)
$data_lifespans = array('live'=>(7200-300),
						'quarterhour'=>(1209600-1800),
						'hour'=>(5184000-7200),
						'month'=>(63113904-60480));
$time = time();
foreach ($db->query('SELECT id, org_id, bos_uuid, url FROM meters WHERE source = \'buildingos\' AND ((gauges_using > 0 OR for_orb > 0 OR timeseries_using > 0) OR bos_uuid IN (SELECT DISTINCT meter_uuid FROM relative_values WHERE permission = \'orb_server\' AND meter_uuid != \'\')) ORDER BY id DESC') as $meter) {
	$bos = new BuildingOS($db, $db->query("SELECT api_id FROM orgs WHERE id = {$meter['org_id']}")->fetchColumn());
	foreach ($resolutions as $res) {
		$stmt = $db->prepare('SELECT recorded FROM meter_data WHERE meter_id = ? AND resolution = ? ORDER BY recorded ASC LIMIT 1');
		$stmt->execute(array($meter['id'], $res));
		$recorded = $stmt->fetchColumn();
		if ($stmt->rowCount() === 0 || $recorded > $time - $data_lifespans[$res]) {
			echo "must update meter {$meter['id']} ({$res})\n";
			$stmt = $db->prepare('DELETE FROM meter_data WHERE meter_id = ? AND resolution = ?');
			$stmt->execute(array($meter['id'], $res));
			$bos->updateMeter($meter['id'], $meter['bos_uuid'], $meter['url'], $res, null);
		}
	}
}

// SELECT DISTINCT meter_id FROM meter_data WHERE resolution = 'live' GROUP BY meter_id HAVING MIN(recorded) > (UNIX_TIMESTAMP() - 6900)
?>



















