<?php
#!/usr/local/bin/php
error_reporting(-1);
ini_set('display_errors', 'On');
chdir(__DIR__);
require '../includes/db.php';
require '../includes/class.BuildingOS.php';
foreach ($db->query('SELECT api_id, url FROM orgs') as $org) {
  $bos = new BuildingOS($db, $org['api_id']);
  $bos->syncBuildings($org['url'], true);
}
?>