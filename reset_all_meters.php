<?php
require '/var/www/repos/includes/db.php';
foreach ($db->query("SELECT id FROM meters WHERE source = 'buildingos' AND (id IN (SELECT meter_id FROM saved_chart_meters) OR id IN (SELECT meter_id FROM gauges) OR bos_uuid IN (SELECT elec_uuid FROM orbs) OR bos_uuid IN (SELECT water_uuid FROM orbs) OR bos_uuid IN (SELECT DISTINCT meter_uuid FROM relative_values WHERE permission = 'orb_server' AND meter_uuid != ''))") as $row) {
  $stmt = $db->prepare('UPDATE meters SET quarterhour_last_updated = -1, hour_last_updated = -1 WHERE id = ?');
  $stmt->execute(array($row['id']));
  $stmt = $db->prepare('DELETE FROM meter_data WHERE meter_id = ? AND (resolution = ? OR resolution = ?)');
  $stmt->execute(array($row['id'], 'quarterhour', 'hour'));
  exec('bash -c "exec nohup setsid /var/repos/daemons/buildingosd -dot -rquarterhour > /dev/null 2>&1 &"');
  exec('bash -c "exec nohup setsid /var/repos/daemons/buildingosd -dot -rhour > /dev/null 2>&1 &"');
  sleep(4);
}
?>
