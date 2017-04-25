<?php
#!/usr/local/bin/php
// Start with: nohup php quarterhour.php > /dev/null &
error_reporting(-1);
ini_set('display_errors', 'On');
chdir(__DIR__);
require '../../includes/db.php';
require '../../includes/class.BuildingOS.php';
require '../../includes/class.Meter.php';
$pid = getmypid();
$res = 'quarterhour';
$meter_obj = new Meter($db);
while (true) {
  set_time_limit(120);
  if ($db->query("SELECT enabled FROM daemons WHERE pid = {$pid}")->fetchColumn() === '0') {
    break;
  }
  $amount = strtotime('-2 weeks');
  $time = time();
  $meter = $db->query('SELECT id, user_id, bos_uuid, url, quarterhour_last_updated FROM meters
    WHERE (gauges_using > 0 OR for_orb > 0 OR orb_server > 0 OR timeseries_using > 0)
    ORDER BY quarterhour_last_updated ASC LIMIT 1')->fetch();
  $bos = new BuildingOS($db, $meter['user_id']);
  $stmt = $db->prepare('UPDATE meters SET last_update_attempt = ? WHERE id = ?');
  $stmt->execute(array($time, $meter['id']));
  echo "Fetching meter #{$meter['id']}\n";
  $stmt = $db->prepare('SELECT recorded FROM meter_data
    WHERE meter_id = ? AND resolution = ? AND value IS NOT NULL
    ORDER BY recorded DESC LIMIT 1');
  $stmt->execute(array($meter['id'], $res));
  if ($stmt->rowCount() === 1) {
    $last_recording = $stmt->fetchColumn();
    $empty = false;
    echo "Last recording at " . date('F j, Y, g:i a', $last_recording) . "\n";
  }
  else {
    $last_recording = $amount;
    $empty = true;
    echo "No data exists for this meter, fetching all data\n";
  }
  $meter_data = $bos->getMeter($meter['url'] . '/data', $res, $last_recording, $time, true);
  $meter_data = json_decode($meter_data, true);
  $meter_data = $meter_data['data'];
  echo "Raw meter data from BuildingOS:\n";
  print_r($meter_data);
  if (!empty($meter_data)) {
    // Clean up old data
    $stmt = $db->prepare("DELETE FROM meter_data WHERE meter_id = ? AND resolution = ? AND recorded < ?");
    $stmt->execute(array($meter['id'], $res, $amount));
    // Delete null data that we're checking again
    $stmt = $db->prepare("DELETE FROM meter_data WHERE meter_id = ? AND resolution = ? AND recorded >= ? AND value IS NULL");
    $stmt->execute(array($meter['id'], $res, $last_recording));
    echo "Query ran: DELETE FROM meter_data WHERE meter_id = {$meter['id']} AND resolution = {$res} AND recorded < {$amount}\n";
    echo "Query ran: DELETE FROM meter_data WHERE meter_id = {$meter['id']} AND resolution = {$res} AND recorded >= {$last_recording}\n";
    echo "Iterating over and inserting data into database:\n";
    $last_value = null;
    $last_recorded = null;
    foreach ($meter_data as $data) { // Insert new data
      $localtime = strtotime($data['localtime']);
      if ($empty || $localtime > $last_recording) {
        $stmt = $db->prepare("INSERT INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)");
        $stmt->execute(array($meter['id'], $data['value'], $localtime, $res));
        if ($data['value'] !== null) {
          $last_value = $data['value'];
          $last_recorded = $localtime;
        }
        echo "{$data['value']} @ {$localtime}\n";
      }
    }
    $stmt = $db->prepare('UPDATE meters SET quarterhour_last_updated = ? WHERE id = ?');
    $stmt->execute(array($time, $meter['id']));
  } // if !empty($meter_data)
  usleep(2000000); // wait for 2 seconds
}
?>