<?php
require '/var/www/repos/includes/db.php';
foreach ($db->query("SELECT id FROM meters WHERE source = 'buildingos' AND (id IN (SELECT meter_id FROM saved_chart_meters) OR id IN (SELECT meter_id FROM gauges) OR bos_uuid IN (SELECT elec_uuid FROM orbs) OR bos_uuid IN (SELECT water_uuid FROM orbs) OR bos_uuid IN (SELECT DISTINCT meter_uuid FROM relative_values WHERE permission = 'orb_server' AND meter_uuid != ''))") as $row) {
	$stmt = $db->prepare('SELECT COUNT(*) FROM meter_data WHERE meter_id = ? AND resolution = ? AND value IS NOT NULL');
  $stmt->execute(array($row['id'], 'hour'));
  $hour_points = $stmt->fetchColumn();
	$stmt = $db->prepare('SELECT COUNT(*) FROM meter_data WHERE meter_id = ? AND resolution = ? AND value IS NOT NULL');
  $stmt->execute(array($row['id'], 'quarterhour'));
  $quarterhour_points = $stmt->fetchColumn();
  // if more than 30% of the data is NULL/nonexistant
  if ($quarterhour_points < (1344 * 0.7)) { // 1344 = # of quarterhour periods in 2 weeks
  	echo "Meter {$row['id']} only has {$quarterhour_points}, 1344 expected\n";
  	try {
  		$stmt = $db->prepare('UPDATE meters SET quarterhour_last_updated = -1, hour_last_updated = -1 WHERE id = ?');
		  $stmt->execute(array($row['id']));
		  $stmt = $db->prepare('DELETE FROM meter_data WHERE meter_id = ? AND (resolution = ? OR resolution = ?)');
		  $stmt->execute(array($row['id'], 'quarterhour', 'hour'));
		  exec('bash -c "exec nohup setsid /var/repos/daemons/buildingosd -dot -rquarterhour > /dev/null 2>&1 &"');
		  sleep(2);
  	} catch (PDOException $e) {
  		echo $e->getMessage();
  	}
  }
  if ($hour_points < (1460 * 0.7)) { // 1460 = # of hours in 2 months
  	echo "Meter {$row['id']} only has {$hour_points}, 1460 expected\n";
  	try {
  		$stmt = $db->prepare('UPDATE meters SET quarterhour_last_updated = -1, hour_last_updated = -1 WHERE id = ?');
		  $stmt->execute(array($row['id']));
		  $stmt = $db->prepare('DELETE FROM meter_data WHERE meter_id = ? AND (resolution = ? OR resolution = ?)');
		  $stmt->execute(array($row['id'], 'quarterhour', 'hour'));
		  exec('bash -c "exec nohup setsid /var/repos/daemons/buildingosd -dot -rquarterhour > /dev/null 2>&1 &"');
		  sleep(2);
  	} catch (PDOException $e) {
  		echo $e->getMessage();
  	}
  }
}
?>
