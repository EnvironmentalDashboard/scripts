<?php
#!/usr/local/bin/php
// like sync_meta_data, but just for one org provided as a command line arg
error_reporting(-1);
ini_set('display_errors', 'On');
chdir(__DIR__);
$options = getopt('o:', array('org:'));
require '../includes/db.php';
require '../includes/class.BuildingOS.php';
if (isset($options['org'])) {
  $org = $options['org'];
} elseif (isset($options['o'])) {
  $org = $options['o'];
} else {
  exit('provide -o or --org option');
}
$stmt = $db->prepare('SELECT api_id, url FROM orgs WHERE id = ?');
$stmt->execute(array($org));
$org = $stmt->fetch();
$bos = new BuildingOS($db, $org['api_id']);
$bos->syncBuildings($org['url'], true);
?>