<?php
require '../../../includes/db.php';
foreach (glob("min/*.php") as $filename) {
  include $filename;
}
?>