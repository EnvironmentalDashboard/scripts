<?php
require '../includes/db.php';
$stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE slug = ?');
$stmt->execute(array($_POST['slug']));
if ($stmt->fetchColumn() > 0) {
  echo 'false';
} else {
  echo 'true';
}
?>