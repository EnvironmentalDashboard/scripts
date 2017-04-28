<?php
#!/usr/local/bin/php
error_reporting(-1);
set_time_limit(0);
ini_set('display_errors', 'On');
date_default_timezone_set('America/New_York');
chdir(__DIR__);
require '../includes/db.php'; // Has $db
$resolutions = array('live', 'quarterhour', 'hour', 'month');
foreach ($db->query('SELECT pid, target_res FROM daemons WHERE enabled = 1') as $daemon) {
  if (!file_exists("/proc/{$daemon['pid']}")) { // process is not running, but is in db
    exec('bash -c "exec nohup setsid php /var/www/html/oberlin/scripts/daemons/'.$daemon['target_res'].'.php > /dev/null 2>&1 &"'); // http://stackoverflow.com/a/3819422/2624391
  }
  if (($key = array_search($daemon['target_res'], $resolutions)) !== false) {
    unset($resolutions[$key]);
  }
}
foreach ($resolutions as $non_existant_res) { // at minimium you need these daemons running
  exec('bash -c "exec nohup setsid php /var/www/html/oberlin/scripts/daemons/'.$non_existant_res.'.php > /dev/null 2>&1 &"');
}
?>