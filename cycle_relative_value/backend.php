<?php
error_reporting(-1);
ini_set('display_errors', 'On');
ignore_user_abort(1);
set_time_limit(0);
require '../../includes/db.php';
$t = (isset($_REQUEST['t'])) ? intval($_REQUEST['t']) : 10;
$v = (isset($_REQUEST['v'])) ? json_decode($_REQUEST['v'], true) : [10, 30, 50, 70, 90];
$count = count($v);
for ($i=0; $i < $count; $i++) { 
	$stmt = $db->prepare("UPDATE relative_values SET relative_value = ? WHERE meter_uuid = '0'");
	$stmt->execute([$v[$i]]);
	if ($i !== ($count-1)) {
		sleep($t);
	}
}
echo 'Success';
?>