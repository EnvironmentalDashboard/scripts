<?php
#!/usr/local/bin/php
// This script scrapes data from the buoys page every 2 mins and inserts the data into the meter_data table
// Other resolutions are calculated by quarterhour.php, hour.php, month.php
// Run with */2 * * * * php /var/www/html/oberlin/scripts/buoys/scrape.php
chdir(__DIR__);
require '../../includes/db.php';
date_default_timezone_set('America/New_York');
// round to get $time right on min mark in case lag in starting script
$time = floor((time()) / 10) * 10; // https://stackoverflow.com/a/1619284/2624391
$file = file_get_contents('http://greatlakesbuoys.org/station_page.php?station=45169');
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
$dom = new DOMDocument();
libxml_use_internal_errors(true); // Disable libxml errors
$dom->loadHTML($file);
$xpath = new DOMXPath($dom);
$rows = $xpath->query('//table[@id="latest-obs"]//td[@class="value "]');
$i = 0;
foreach ($rows as $row) { // each td value will be a $row
  // Extract float and insert into db
  $val = (float) filter_var($row->nodeValue, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
  foreach ($ids[$i++] as $id) {
    $stmt = $db->prepare('INSERT INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)');
    $stmt->execute(array($id, $val, $time, 'live'));
  }
}
$stmt = $db->prepare('DELETE FROM meter_data WHERE meter_id >= ? AND meter_id <= ? AND resolution = ? AND recorded < ?');
$stmt->execute(array(1906, 1960, 'live', $time - 10800)); // 10800s is 3 hours i.e. delete data older than 3 hours
?>
