<?php
#!/usr/local/bin/php
// Start with: nohup php live.php > /dev/null &
error_reporting(-1);
ini_set('display_errors', 'On');
chdir(__DIR__);
require '../../includes/db.php';
require '../../includes/class.BuildingOS.php';
require '../../includes/class.Meter.php';
$res = 'live';
$meter_obj = new Meter($db);
$pid = getmypid();
$stmt = $db->prepare('INSERT INTO daemons (pid, enabled, target_res) VALUES (?, b\'1\', ?)');
$stmt->execute(array($pid, $res));
function shutdown() {
  global $db; // since it's a callback function it can't have args so have to do this instead
  $stmt = $db->prepare('DELETE FROM daemons WHERE pid = ?');
  $stmt->execute(array(getmypid()));
}
register_shutdown_function('shutdown');
while (true) {
  set_time_limit(200); // If a single iteration takes longer than 200s, exit
  if ($db->query("SELECT enabled FROM daemons WHERE pid = {$pid}")->fetchColumn() === '0') {
    // If enabled column turned off, exit
    shutdown();
    break; 
  }
  $meter = $db->query('SELECT id, org_id, bos_uuid, url, live_last_updated FROM meters
    WHERE (gauges_using > 0 OR for_orb > 0 OR timeseries_using > 0) OR bos_uuid IN (SELECT DISTINCT meter_uuid FROM relative_values WHERE permission = \'orb_server\' AND meter_uuid != \'\')
    AND id NOT IN (SELECT updating_meter FROM daemons WHERE target_res = \'live\')
    AND source = \'buildingos\'
    ORDER BY live_last_updated ASC LIMIT 1')->fetch(); // Select the least up to date meter
  $db->query("UPDATE daemons SET updating_meter = {$meter['id']}, memory_usage = ".memory_get_usage(true).", memory_peak_usage = ".memory_get_peak_usage(true)." WHERE pid = {$pid}");
  if ($meter['live_last_updated'] > time() - 120) { // if last reading more recent than 2 mins, sleep
    sleep(60);
  }
  $bos = new BuildingOS($db, $meter['org_id']); // Create an instance of the BuildingOS class that can make calls to the API using the information associated with the org_id
  $bos->updateMeter($meter['id'], $meter['bos_uuid'], $meter['url'] . '/data', $res, $meter_obj);
  $bos = null; // free for garbage collector
  // $fp = fopen("/root/daemon_logs/{$pid}.log", 'w');
  // fwrite($fp, "Last iteration completed on " . date('F j, Y, g:i a') . "\n\n");
  // fwrite($fp, "Data from meter #{$meter['id']}:\n" . var_export($meter_data, true) . "\n");
  // fclose($fp);
  // $stmt = $db->prepare('INSERT INTO bos_log (data, url, res, start, `end`, run) VALUES (?, ?, ?, ?, ?, ?)');
  // $stmt->execute($params);
}
?>