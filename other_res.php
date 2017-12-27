<?php
require '/var/www/html/oberlin/includes/db.php';
foreach ($db->query("SELECT id FROM meters WHERE calculated = 1 AND source = 'buildingos' AND ((gauges_using > 0 OR for_orb > 0 OR timeseries_using > 0) OR bos_uuid IN (SELECT DISTINCT meter_uuid FROM relative_values WHERE permission = 'orb_server' AND meter_uuid != ''))") as $row) {
  $stmt = $db->prepare('UPDATE meters SET quarterhour_last_updated = -1, hour_last_updated = -1 WHERE id = ?');
  $stmt->execute(array($row['id']));
  exec('bash -c "exec nohup setsid /var/www/html/oberlin/daemons/buildingosd -dot -rquarterhour > /dev/null 2>&1 &"');
  exec('bash -c "exec nohup setsid /var/www/html/oberlin/daemons/buildingosd -dot -rhour > /dev/null 2>&1 &"');
  sleep(2);
}
?>
