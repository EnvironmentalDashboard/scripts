<?php
foreach ($db->query('SHOW TABLES') as $table) {
  foreach ($db->query('SHOW COLUMNS FROM oberlin_environmentaldashboard.meters') as $col) {
    if ($col['Type'] === 'blob') {
      foreach ($db->query("SELECT {$col['Field']} FROM ") as $key => $value) {
        # code...
      }
    }
  }
}
?>