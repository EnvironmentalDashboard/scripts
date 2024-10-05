<?php
error_reporting(-1);
ini_set('display_errors', 'On');
require '../includes/db.php';
require '../includes/class.BuildingOS.php';
if (isset($_POST['submit'])) {
	if ($_POST['id_type'] === 'uuid') {
		$uuid = $_POST['id'];
		$stmt = $db->prepare('SELECT org_id, id FROM meters WHERE bos_uuid = ?');
		$stmt->execute(array($uuid));
		$res = $stmt->fetch();
		$id = $res['id'];
	} else {
		$id = $_POST['id'];
		$stmt = $db->prepare('SELECT org_id, bos_uuid FROM meters WHERE id = ?');
		$stmt->execute(array($id));
		$res = $stmt->fetch();
		$uuid = $res['bos_uuid'];
	}
	$stmt = $db->prepare('SELECT api_id FROM orgs WHERE id = ?');
	$stmt->execute([$res['org_id']]);
	$bos = new BuildingOS($db, $stmt->fetchColumn());
	$bos->resetMeter($id, 'live');
	$bos->resetMeter($id, 'quarterhour');
	$bos->resetMeter($id, 'hour');
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
		echo "<p>Updated meter {$uuid}</p>";
	} ?>
	<form action="" method="POST">
		<input type="radio" name="id_type" value="uuid" checked>BuildingOS meter ID<br>
		<input type="radio" name="id_type" value="id">Dashboard meter ID<br>
		<input type="text" name="id" placeholder="ID">
		<input type="submit" name="submit" value="Update meter">
	</form>
</body>
</html>