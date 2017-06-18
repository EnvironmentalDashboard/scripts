<?php
#!/usr/local/bin/php
// This script calculates month data for meters 1906-1960
// Run with 0 0 1 * * php /var/www/html/oberlin/scripts/buoys/month.php
date_default_timezone_set('America/New_York');
// floor time to nearest 10 to get recorded values that are exactly on the second in case some lag when starting script
$time = floor((time()) / 10) * 10; // https://stackoverflow.com/a/1619284/2624391
$start = strtotime('-1 month');
chdir(__DIR__);
require '../../includes/db.php';
$ids = array(
  array(1906, 1908, 1909), // the meter ids that are the first data point read in the foreach loop (wind speed)
  array(1911, 1912, 1913),
  array(1914, 1916, 1917),
  array(1918, 1919, 1920),
  array(1921, 1922, 1923),
  array(1924, 1925, 1926),
  array(1927, 1928, 1929),
  array(1930, 1932, 1933),
  array(1934, 1935, 1936),
  array(1937, 1938, 1939),
  array(1940, 1941, 1942),
  array(1943, 1944, 1945),
  array(1946, 1947, 1948),
  array(1949, 1950, 1951),
  array(1952, 1953, 1954),
  array(1955, 1956, 1957),
  array(1958, 1959, 1960));
foreach ($ids as $id) {
  $stmt = $db->prepare('SELECT AVG(value) FROM meter_data WHERE resolution = ? AND recorded >= ? AND meter_id = ?');
  $stmt->execute(array('hour', $start, $id[0]));
  $val = $stmt->fetchColumn();
  foreach ($id as $i) {
    $stmt = $db->prepare('INSERT INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)');
    $stmt->execute(array($i, $val, $time, 'month'));
  }
}
$stmt = $db->prepare('DELETE FROM meter_data WHERE meter_id >= ? AND meter_id <= ? AND resolution = ? AND recorded >= ?');
$stmt->execute(array(1906, 1960, 'month', strtotime('-2 years')));
?>