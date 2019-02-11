<?php
require '../../includes/db.php';
foreach ($db->query("SELECT DISTINCT name FROM time_series WHERE length = 0") as $row) {
  if (file_exists("/var/www/repos/chart/images/{$row['name']}.gif")) {
    $res = intval(shell_exec("python ./gifduration-script/gifduration.py /var/www/repos/chart/images/{$row['name']}.gif"));
    $stmt = $db->prepare('UPDATE time_series SET length = ? WHERE name = ?');
    $stmt->execute([$res, $row['name']]);
  }
}
