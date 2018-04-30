<?php
error_reporting(-1);
ini_set('display_errors', 'On');
ignore_user_abort(1);
set_time_limit(0);
require '../../includes/db.php';
$t = (isset($_REQUEST['t'])) ? intval($_REQUEST['t']) : 10;
$v = [10, 30, 50, 70, 90];
if (isset($_REQUEST['v'])) {
	$arr = explode(',', $_REQUEST['v']);
	if (count($arr) == 5) {
		$v = $arr;
	}
}
for ($i=0; $i < 5; $i++) { 
	$stmt = $db->prepare("UPDATE relative_values SET relative_value = ? WHERE meter_uuid = '0'");
	$stmt->execute([$v[$i]]);
	if ($i !== 4) {
		sleep($t);
	}
}
echo 'Success';
?>