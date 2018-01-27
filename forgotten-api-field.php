<?php
// updated a column in the meters table with new info from api if we decide to add another column, etc.
require '../includes/db.php';
require '../includes/class.BuildingOS.php';
foreach ($db->query("SELECT id, org_id, url FROM meters WHERE scope = '' AND source = 'buildingos'") as $row) {
	$stmt = $db->prepare("SELECT id FROM api WHERE user_id IN (SELECT user_id FROM users_orgs_map WHERE org_id = ?)");
	$stmt->execute([$row['org_id']]);
	$api_id = $stmt->fetchColumn();
	$api = new BuildingOS($db, $api_id);
	$resp = json_decode($api->makeCall($row['url']), true);
	$scope = $resp['data']['scope']['displayName'];
	$stmt = $db->prepare('UPDATE meters SET scope = ? WHERE id = ?');
	$stmt->execute([$scope, $row['id']]);
}