<?php
error_reporting(-1);
ini_set('display_errors', 'On');
chdir(__DIR__);
require '../includes/db.php';
foreach ($db->query('SELECT id, img FROM calendar WHERE img IS NOT NULL') as $event) {
  $img = imagecreatefromstring($event['img']);
  if (!$img) {
    continue;
  }
  $mime = getimagesizefromstring($event['img'])['mime'];
  $ext = explode('/', $mime)[1];
  ob_start();
  if ($mime === 'image/jpeg') {
    imagejpeg($img);
  } else {
    imagepng($img);
  }
  $output = ob_get_clean();
  $fn = "tmp.{$ext}";
  file_put_contents($fn, $output);
  shell_exec("convert {$fn} -define {$ext}:extent=52kb output.{$ext}"); // https://stackoverflow.com/a/11920384/2624391
  if (filesize("output.{$ext}") < 65535) {
    $fp = fopen("output.{$ext}", 'rb');
    $stmt = $db->prepare('UPDATE calendar SET thumbnail = ? WHERE id = ?');
    $stmt->bindParam(1, $fp, PDO::PARAM_LOB);
    $stmt->bindParam(2, $event['id']);
    $stmt->execute();
  } else {
    $stmt = $db->prepare('UPDATE calendar SET thumbnail = ? WHERE id = ?');
    $stmt->execute([null, $event['id']]);
  }
  unlink("output.{$ext}");
  unlink($fn);
}
?>