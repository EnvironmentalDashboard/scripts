<?php
#!/usr/local/bin/php
// Start with: nohup php hour.php > /dev/null &
error_reporting(-1);
ini_set('display_errors', 'On');
chdir(__DIR__);
require '../../includes/db.php';
require '../../includes/class.BuildingOS.php';
require '../../includes/class.Meter.php';
$pid = getmypid();
$stmt = $db->prepare('INSERT INTO daemons (pid, enabled) VALUES (?, b\'1\')');
$stmt->execute(array($pid));
function shutdown() {
  global $db; // since it's a callback function it can't have args so have to do this instead
  $stmt = $db->prepare('DELETE FROM daemons WHERE pid = ?');
  $stmt->execute(array(getmypid()));
}
register_shutdown_function('shutdown');
$res = 'hour';
$meter_obj = new Meter($db);
while (true) {
  set_time_limit(100); // If a single iteration takes longer than 100s, exit
  if ($db->query("SELECT enabled FROM daemons WHERE pid = {$pid}")->fetchColumn() === '0') {
    // If enabled column turned off, exit
    shutdown();
    break; 
  }
  $meter = $db->query('SELECT id, user_id, bos_uuid, url, hour_last_updated FROM meters
    WHERE (gauges_using > 0 OR for_orb > 0 OR orb_server > 0 OR timeseries_using > 0)
    ORDER BY hour_last_updated ASC LIMIT 1')->fetch(); // Select the least up to date meter
  if ($meter['hour_last_updated'] > time() - 600) { // if last reading more recent than 10 mins, sleep
    sleep(400);
  }
  $bos = new BuildingOS($db, $meter['user_id']); // Create an instance of the BuildingOS class that can make calls to the API using the information associated with the user_id
  $bos->updateMeter($meter['id'], $meter['bos_uuid'], $meter['url'] . '/data', $res, $meter_obj);
  $bos = null; // free for garbage collector
}
?>