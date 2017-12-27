<?php
require '../includes/db.php';
if (isset($_POST['uuid'])) {
	if ($_POST['id_type'] === 'uuid') {
		$uuid = $_POST['id'];
		$stmt = $db->prepare('SELECT id FROM meters WHERE bos_uuid = ?');
		$stmt->execute(array($uuid));
		$id = $stmt->fetchColumn();
	} else {
		$id = $_POST['id'];
		$stmt = $db->prepare('SELECT bos_uuid FROM meters WHERE id = ?');
		$stmt->execute(array($id));
		$uuid = $stmt->fetchColumn();
	}
	$stmt = $db->prepare('DELETE FROM meter_data WHERE meter_id = ? AND resolution != ?');
	$stmt->execute(array($id, 'live'));
	$stmt = $db->prepare('UPDATE meters SET quarterhour_last_updated = -1, hour_last_updated = -1 WHERE bos_uuid = ?');
	$stmt->execute(array($uuid));
	exec('bash -c "exec nohup setsid /var/www/html/oberlin/daemons/buildingosd -dot -rquarterhour > /dev/null 2>&1 &"');
	exec('bash -c "exec nohup setsid /var/www/html/oberlin/daemons/buildingosd -dot -rhour > /dev/null 2>&1 &"');
	$success = true;
} else {
	$success = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Document</title>
</head>
<body>
	<?php if ($success) {
		echo "<p>Updated meter {$_POST['uuid']}</p>";
	} ?>
	<form action="" method="POST">
		<input type="radio" name="id_type" value="uuid" checked>BuildingOS meter ID<br>
		<input type="radio" name="id_type" value="id">Dashboard meter ID<br>
		<input type="text" name="id" placeholder="ID">
		<input type="submit">
	</form>
</body>
</html>