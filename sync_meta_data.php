<?php
#!/usr/local/bin/php
error_reporting(-1);
ini_set('display_errors', 'On');
chdir(__DIR__);
require '../includes/db.php';
require '../includes/class.BuildingOS.php';
// Get all the users that have organizations specified for them
foreach ($db->query('SELECT api_id, orgs FROM users WHERE CHAR_LENGTH(orgs) > 10') as $user) {
  $bos = new BuildingOS($db, $user['api_id']);
  $bos->syncBuildings(json_decode($user['orgs'], true), true);
  $bos = null;
  sleep(3); // the api gets mad if it's queried too quickly
}
?>