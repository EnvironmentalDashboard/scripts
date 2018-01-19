<?php
error_reporting(-1);
ini_set('display_errors', 'On');
require '/var/www/repos/includes/db.php';
$time = time();
$res = 'hour';
$recorded = floor(($time - 3600) / 10) * 10; // round down time to nearest 10 to make sure $recorded occurs exactly at the start of the time interval
openlog('15min', LOG_PID | LOG_ODELAY, LOG_CRON);
foreach ($db->query("SELECT id FROM meters WHERE source = 'buildingos' AND ((gauges_using > 0 OR for_orb > 0 OR timeseries_using > 0) OR bos_uuid IN (SELECT DISTINCT meter_uuid FROM relative_values WHERE permission = 'orb_server' AND meter_uuid != ''))") as $meter) {
	$stmt = $db->prepare('SELECT value FROM meter_data WHERE meter_id = ? AND value IS NOT NULL AND resolution = ? AND recorded >= ?');
	$stmt->execute([$meter['id'], $res, $recorded]);
	if ($stmt->rowCount() === 0) {
		syslog(LOG_WARNING, "Can not calculate hour data for meter {$meter['id']}; No data exists from {$recorded} to {$time}");
		$stmt = $db->prepare("INSERT INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)");
		$stmt->execute([$meter['id'], null, $recorded, $res]);
	} else {
		$arr = [];
		foreach ($stmt->fetchAll() as $row) {
			$arr[] = $row['value'];
		}
		$stmt = $db->prepare("INSERT INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)");
		$avg = array_sum($arr)/count($arr);
		$stmt->execute([$meter['id'], $avg, $recorded, $res]);
	}
}
?>