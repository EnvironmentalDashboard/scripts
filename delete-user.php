<?php 
require '../includes/db.php';
$options = getopt('', array('id:'));
if (array_key_exists('id', $options)) {
  $stmt = $db->prepare('SELECT name FROM users WHERE id = ?');
  $stmt->execute(array($options['id']));
  $name = $stmt->fetchColumn();
  $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
  $stmt->execute(array($options['id']));
  $stmt = $db->prepare('DELETE FROM api WHERE user_id = ?');
  $stmt->execute(array($options['id']));
  $stmt = $db->prepare('DELETE FROM cwd_bos WHERE user_id = ?');
  $stmt->execute(array($options['id']));
  $stmt = $db->prepare('DELETE FROM cwd_landscape_components WHERE user_id = ?');
  $stmt->execute(array($options['id'])); // In the future we might also want to delete all the images on the server associated with the records being deleted
  $stmt = $db->prepare('DELETE FROM cwd_messages WHERE user_id = ?');
  $stmt->execute(array($options['id']));
  $stmt = $db->prepare('DELETE FROM cwd_states WHERE user_id = ?');
  $stmt->execute(array($options['id']));
  $stmt = $db->prepare('DELETE FROM time_series WHERE user_id = ?');
  $stmt->execute(array($options['id']));
  $stmt = $db->prepare('DELETE FROM timing WHERE user_id = ?');
  $stmt->execute(array($options['id']));
  shell_exec('rm '.escapeshellarg("/var/www/html/{$name}"));
} else {
  echo "Example usage: php delete-user.php --id=\"0\"\n";
}
?>