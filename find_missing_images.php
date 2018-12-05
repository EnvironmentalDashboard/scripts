<?php
require '../community-voices/src/CommunityVoices/App/Website/db.php';

foreach ($dbHandler->query('SELECT filename FROM `community-voices_images`') as $row) {
	if (!file_exists($row['filename'])) {
		file_put_contents('list_of_files', basename($row['filename']).PHP_EOL , FILE_APPEND | LOCK_EX);
	}
}
