<?php
date_default_timezone_set('America/New_York');
openlog('Travis CI', LOG_PID | LOG_ODELAY, LOG_AUTH); // logs will be sent to /var/log/auth.log
$time = date('c');
// notifications will be sent from these IPs - see https://docs.travis-ci.com/user/notifications/#note-on-ip-addresses
if (in_array($_SERVER['REMOTE_ADDR'], ['54.173.229.200', '54.175.230.252'])) {
	if (isset($_POST['payload'])) {
		$payload = json_decode($_POST['payload']);
		if ($payload['result'] === 0) {
			if ($_GET['repo'] === 'community-voices') {
				shell_exec("cd /var/www/repos/community-voices && git pull");
			} elseif ($_GET['repo'] === 'daemons') {
				shell_exec('cd /var/repos/daemons && git pull && ' . dockerStopByImage('buildingosd') . ' && ./run.sh');
			} elseif ($_GET['repo'] === 'relative-value') {
				shell_exec('cd /var/repos/relative-value && git pull && ' . dockerStopByImage('relative-value-img') . ' && ./run.sh');
			} else {
				syslog(LOG_NOTICE, "Invalid Travis CI repo: " . json_encode($_GET));
			}
		} else {
			syslog(LOG_NOTICE, "Invalid Travis CI payload: " . json_encode($_POST));	
		}
	} else {
		syslog(LOG_NOTICE, "No Travis CI payload: " . json_encode($_POST));
	}
} else {
	syslog(LOG_NOTICE, "Unauthorized Travis CI POST: {$time} {$_SERVER['REMOTE_ADDR']} ({$_SERVER['HTTP_USER_AGENT']})");
}

closelog();

function dockerStopByImage($image) {
	// https://stackoverflow.com/a/32074098/2624391
	return "docker rm $(docker stop $(docker ps -a -q --filter ancestor={$image} --format=\"{{.ID}}\"))";
}
