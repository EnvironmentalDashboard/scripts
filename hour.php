<?php
error_reporting(-1);
ini_set('display_errors', 'On');
require '/var/www/repos/includes/db.php';
$time = time();
$res = 'hour';
$end = floor(($time - 3600) / 10) * 10;
$start = $end - 3600;
openlog('15min', LOG_PID | LOG_ODELAY, LOG_CRON);
foreach ($db->query("SELECT id FROM meters WHERE source = 'buildingos' AND ((gauges_using > 0 OR for_orb > 0 OR timeseries_using > 0) OR bos_uuid IN (SELECT DISTINCT meter_uuid FROM relative_values WHERE permission = 'orb_server' AND meter_uuid != ''))") as $meter) {
	$stmt = $db->prepare('SELECT value FROM meter_data WHERE meter_id = ? AND value IS NOT NULL AND resolution = ? AND recorded >= ? AND recorded <= ?');
	$stmt->execute([$meter['id'], 'live', $start, $end]);
	if ($stmt->rowCount() === 0) {
		syslog(LOG_WARNING, "Can not calculate hour data for meter {$meter['id']}; No data exists from ".date('c', $start)." to ".date('c', $end));
		$stmt = $db->prepare("REPLACE INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)");
		$stmt->execute([$meter['id'], null, $end, $res]);
	} else {
		$arr = [];
		foreach ($stmt->fetchAll() as $row) {
			$arr[] = $row['value'];
		}
		$stmt = $db->prepare("REPLACE INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)");
		$avg = array_sum($arr)/count($arr);
		$stmt->execute([$meter['id'], $avg, $end, $res]);
	}
}
?>