<?php
require '../../../includes/db.php';
foreach (glob("min30/*.php") as $filename) {
  include $filename;
}
?>