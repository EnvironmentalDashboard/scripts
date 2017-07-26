<?php
require '../includes/db.php';
foreach ($db->query("SELECT * FROM `meters` WHERE scope = 'Whole Building' AND ((gauges_using = 0 AND for_orb = 0 AND timeseries_using = 0) AND bos_uuid NOT IN (SELECT DISTINCT meter_uuid FROM relative_values WHERE permission = 'orb_server' AND meter_uuid != '')) ORDER BY org_id") as $row) {
  $q = array(
    ':user_id' => $db->query("SELECT user_id FROM users_orgs_map WHERE org_id = {$row['org_id']} ORDER BY user_id ASC LIMIT 1")->fetchColumn(),
    ':meter_id' => $row['id'],
    ':dasharr1' => null,
    ':fill1' => 'on',
    ':meter_id2' => $row['id'],
    ':dasharr2' => null,
    ':fill2' => 'on',
    ':dasharr3' => null,
    ':fill3' => 'on',
    ':start' => null,
    ':ticks' => 0,
    ':color1' => '#00a185',
    ':color2' => '#bdc3c7',
    ':color3' => '#33a7ff',
    ':label' => null
  );
  $stmt = $db->prepare('INSERT INTO time_series_configs (user_id, meter_id, meter_id2, dasharr1, fill1, dasharr2, fill2, dasharr3, fill3, start, ticks, color1, color2, color3, label)
    VALUES (:user_id, :meter_id, :meter_id2, :dasharr1, :fill1, :dasharr2, :fill2, :dasharr3, :fill3, :start, :ticks, :color1, :color2, :color3, :label)');
  $stmt->execute($q);
  $stmt = $db->prepare('UPDATE meters SET timeseries_using = timeseries_using + 1 WHERE id = ?');
  $stmt->execute(array($row['id']));
}
?>