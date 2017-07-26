<?php
require '../../../includes/db.php';
foreach (glob("hour/*.php") as $filename) {
  include $filename;
}
?>