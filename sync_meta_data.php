<?php
#!/usr/local/bin/php
error_reporting(-1);
ini_set('display_errors', 'On');
chdir(__DIR__);
require '../includes/db.php';
require '../includes/class.BuildingOS.php';
foreach ($db->query('SELECT id, url FROM orgs') as $org) {
  $bos = new BuildingOS($db, $org['id']);
  $arr = json_decode($org['orgs'], true);
  if ($arr === false) {
    $arr = array();
  }
  $bos->syncBuildings(array($org['url']), true);
}
?>