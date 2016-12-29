<?php
require '../../../includes/db.php';
foreach (glob("day/*.php") as $filename) {
  include $filename;
}
?>