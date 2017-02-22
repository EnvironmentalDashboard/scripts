<?php
require '/var/www/html/oberlin/includes/db.php';
require '/var/www/html/oberlin/includes/class.Meter.php';
$uuid = 'oberlin_ajlc_h2ohote';
$id = 1;
$stmt = $db->prepare('SELECT id, grouping FROM relative_values WHERE meter_uuid = ?');
$stmt->execute(array($uuid));
$day_of_week = date('w') + 1; // https://dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_dayofweek
foreach ($stmt->fetchAll() as $rv_row) {
  // Example JSON: [{"days":[1,2,3,4,5],"npoints":8},{"days":[1,7],"npoints":5}]
  foreach (json_decode($rv_row['grouping'], true) as $group) {
    if (in_array($day_of_week, $group['days'])) {
      $days = $group['days'];
      if (array_key_exists('npoints', $group)) {
        $amount = intval($group['npoints']);
        $days = implode(',', array_map('intval', $days)); // prevent sql injection with intval as we're concatenating directly into query
        $stmt = $db->prepare(
          "SELECT value FROM meter_data
          WHERE meter_id = ? AND value IS NOT NULL AND resolution = ?
          AND HOUR(FROM_UNIXTIME(recorded)) = HOUR(NOW())
          AND DAYOFWEEK(FROM_UNIXTIME(recorded)) IN ({$days})
          ORDER BY recorded DESC LIMIT " . $amount); // ORDER BY recorded DESC is needed because we're trying to extract the most recent $amount points
        $stmt->execute(array($id, 'hour'));
        $relative_value = $meter->relativeValue(array_map('floatval', array_column($stmt->fetchAll(), 'value')), $last_value);
      } else if (array_key_exists('start', $group)) {
        $amount = intval($group['start']);
        $days = implode(',', array_map('intval', $days));
        $stmt = $db->prepare(
          "SELECT value, recorded FROM meter_data
          WHERE meter_id = ? AND value IS NOT NULL
          AND recorded > ? AND recorded < ? AND resolution = ?
          AND HOUR(FROM_UNIXTIME(recorded)) = HOUR(NOW())
          AND DAYOFWEEK(FROM_UNIXTIME(recorded)) IN ({$days})
          ORDER BY value ASC"); // ORDER BY value ASC is efficient here because the relativeValue() method will sort the data like this (and there's no need to sort by recorded -- the amount of data is determined by $amount, which is a unix timestamp representing when the data should start)
        $stmt->execute(array($id, $amount, time(), 'hour'));
        $relative_value = $meter->relativeValue($stmt->fetchAll(), $last_value);
      }
      $stmt = $db->prepare('UPDATE relative_values SET relative_value = ? WHERE meter_uuid = ?');
      $stmt->execute(array(round($relative_value), $rv_row['bos_uuid']));
      break;
    } // if
  } // foreach
} // foreach
?>