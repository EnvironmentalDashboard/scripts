<?php
// It's not likely this file will ever be used again
require '/var/www/html/oberlin/includes/db.php';
// foreach ($db->query('SELECT meter_uuid FROM gauges') as $row) {
//   $stmt = $db->prepare('INSERT INTO relative_values (meter_uuid, grouping, relative_value, permission) VALUES (?, ?, ?, ?)');
//   $stmt->execute(array($row['meter_uuid'], '[{"days":[2,3,4,5,6],"npoints":5},{"days":[1,7],"npoints":5}]', 0, 'gauges'));
// }
// foreach ($db->query('SELECT meter_uuid FROM gauges') as $row) {
//   $stmt = $db->prepare('SELECT id FROM relative_values WHERE meter_uuid = ? AND permission = ? LIMIT 1');
//   $stmt->execute(array($row['meter_uuid'], 'gauges'));
//   $id = $stmt->fetchColumn();
//   $stmt = $db->prepare('UPDATE gauges SET rv_id = ? WHERE meter_uuid = ?');
//   $stmt->execute(array($id, $row['meter_uuid']));
// }
// 
// also replace the orbs
// 
foreach ($db->query('SELECT id, elec_uuid, water_uuid FROM orbs') as $row) {
  $stmt = $db->prepare('INSERT INTO relative_values (meter_uuid, grouping, relative_value, permission) VALUES (?, ?, ?, ?)');
  $stmt->execute(array($row['elec_uuid'], '[{"days":[2,3,4,5,6],"npoints":7},{"days":[1,7],"npoints":5}]', 0, 'old_orbs'));
  $elecid = $db->lastInsertId();
  $stmt = $db->prepare('INSERT INTO relative_values (meter_uuid, grouping, relative_value, permission) VALUES (?, ?, ?, ?)');
  $stmt->execute(array($row['water_uuid'], '[{"days":[2,3,4,5,6],"npoints":7},{"days":[1,7],"npoints":5}]', 0, 'old_orbs'));
  $waterid = $db->lastInsertId();
  $stmt = $db->prepare('UPDATE orbs SET elec_rvid = ?, water_rvid = ? WHERE id = ?');
  $stmt->execute(array($elecid, $waterid, $row['id']));
}
?>