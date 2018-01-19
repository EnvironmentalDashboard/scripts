<?php
/* OUTDATED
#!/usr/local/bin/php
chdir(__DIR__);
require '../includes/db.php';
require '../includes/class.BuildingOS.php';
$options = getopt('', array('name:', 'slug:', 'client_id:', 'client_secret:', 'username:', 'password:'));
$ok = true;
foreach (array('name', 'slug', 'client_id', 'client_secret', 'username', 'password') as $required_option) {
  if (!array_key_exists($required_option, $options)) {
    $ok = false;
    break;
  }
}
if ($ok) {
  $stmt = $db->prepare('INSERT INTO api (client_id, client_secret, username, password) VALUES (?, ?, ?, ?)'); // the all the records in the api table will be
  $stmt->execute(array($options['client_id'], $options['client_secret'], $options['username'], $options['password']));
  $api_id = $db->lastInsertId();
  // the name column will be used to match the url, see ~/includes/db.php
  $stmt = $db->prepare('INSERT INTO users (api_id, slug, name) VALUES (?, ?)');
  $stmt->execute(array($api_id, $options['slug'], $options['name']));
  $user_id = $db->lastInsertId();
  $bos = new BuildingOS($db, $api_id);
  $bos->populateDB($user_id); // Retrieve buildings/meters from BuildingOS API
  shell_exec('ln -s /var/www/repos '.escapeshellarg("/var/www/html/{$options['name']}")); // Create new symlink
  $db->exec("INSERT INTO cwd_bos (user_id, squirrel, fish, water_speed, electricity_speed, landing_messages, electricity_messages, gas_messages, stream_messages, water_messages, weather_messages) SELECT {$user_id}, squirrel, fish, water_speed, electricity_speed, landing_messages, electricity_messages, gas_messages, stream_messages, water_messages, weather_messages FROM cwd_bos WHERE user_id = 1");
  $db->exec("INSERT INTO cwd_landscape_components (user_id, component, pos, widthxheight, title, link, img, `text`, text_pos, `order`, removable, hidden) SELECT {$user_id}, component, pos, widthxheight, title, link, img, `text`, text_pos, `order`, removable, hidden FROM cwd_landscape_components WHERE user_id = 1");
  $db->exec("INSERT INTO cwd_messages (user_id, resource, message, prob1, prob2, prob3, prob4, prob5) SELECT {$user_id}, resource, message, prob1, prob2, prob3, prob4, prob5 FROM cwd_messages WHERE user_id = 1");
  $db->exec("INSERT INTO cwd_states (resource, user_id, gauge1, gauge2, gauge3, gauge4, `on`) SELECT resource, {$user_id}, gauge1, gauge2, gauge3, gauge4, `on` FROM cwd_states WHERE user_id = 1");
  $db->exec("INSERT INTO time_series (name, user_id, length, bin1, bin2, bin3, bin4, bin5) SELECT name, {$user_id}, length, bin1, bin2, bin3, bin4, bin5 FROM time_series WHERE user_id = 1");
  $db->exec("INSERT INTO timing (user_id, message_section, delay, interval) SELECT {$user_id}, message_section, delay, interval FROM timing WHERE user_id = 1");
} else {
  echo 'Example usage: php add-user.php --name="City name" --slug="city-name" --client_id="..." --client_secret="..." --username="..." --password="..."';
  echo "\nAll options are required\n";
}
?>