<?php
require '../../../includes/db.php';
foreach (glob("min10/*.php") as $filename) {
  include $filename;
}
?>