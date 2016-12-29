<?php
error_reporting(-1);
ini_set('display_errors', 'On');
/**
 * Cron job to access BuildingOS API and update meter data in database.
 * This file has the cron() function. Look at ~/scripts/jobs/ for their usage.
 *
 * The idea here is that requesting large amounts of data from the API is too slow so data is cached in MySQL
 * The first time the cron job runs all data should be fetched form the API. After that, the jobs at the various intervals get the most recent reading (which, aside from the minutely job, are all calculated with existing data)
 *
 * The job at the 1 minute interval is for collecting minute resolution meter data (going back 2 hours) and updating meters current values
 * The job at the 15 minute interval is for collecting quarterhour resolution meter data (going back 2 weeks)
 * The job at the 1 hour interval is for collecting hour resolution meter meter data (going back 2 months)
 * The job at the 1 month interval is for collecting month resolution meter data (going back 2 years)
 *
 * @author Tim Robert-Fitzgerald June 2016
 */
function cron($db, $bos, $res, $amount, $update_current = false, $update_units = false) {
  sleep(3);
  $time = time();
  foreach ($db->query('SELECT id, url FROM meters WHERE (num_using > 0 OR for_orb = 1) AND source = \'buildingos\' ORDER BY RAND()') as $row) {
    echo "Fetching meter #{$row['id']}\n";
    // Check to see what the last recorded value is
    // I just added 'AND value IS NOT NULL' because sometimes BuidlingOS returns null data and later fixes it? ...weird
    $stmt = $db->prepare('SELECT recorded FROM meter_data
      WHERE meter_id = ? AND resolution = ? AND value IS NOT NULL
      ORDER BY recorded DESC LIMIT 1');
    $stmt->execute(array($row['id'], $res));
    if ($stmt->rowCount() === 1) {
      $last_recording = $stmt->fetch()['recorded'];
      $empty = false;
      echo "Last recording at " . date('F j, Y, g:i a', $last_recording) . "\n";
    }
    else {
      $last_recording = $amount;
      $empty = true;
      echo "No data exists for this meter, fetching all data\n";
    }

    /*
    // If the resolution isn't live try to calculate the data from cached data rather than using the API
    if ($res === 'quarterhour' && $last_recording >= strtotime('-2 hours')) {
      $stmt->prepare('SELECT value, recorded FROM meter_data
      WHERE meter_id = ? AND resolution = ? AND recorded >= ?');
      $was = $last_recording;
      $arr = array();
      foreach ($stmt->execute(array($row['id'], 'live', $last_recording)) as $cached_meter) {
        if ($cached_meter['recorded'] - $was >= 900) { // 15 minutes = 900 secs
          // If we've hit our 15 min interval average data and insert into db
          $stmt = $db->prepare("INSERT INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)");
          $stmt->execute(array($row['id'], array_sum($arr) / count($arr), $was + 900, $res));
          $was = $cached_meter['recorded'];
          unset($arr);
          $arr = array();
        }
        array_push($arr, $cached_meter['value']);
      }
      return;
    }
    elseif ($res === 'hour' && $last_recording >= strtotime('-2 weeks')) {
      $stmt->prepare('SELECT value, recorded FROM meter_data
      WHERE meter_id = ? AND resolution = ? AND recorded >= ?');
      $was = $last_recording;
      $arr = array();
      foreach ($stmt->execute(array($row['id'], 'quarterhour', $last_recording)) as $cached_meter) {
        if ($cached_meter['recorded'] - $was >= 3600) { // 1 hour = 3600 secs
          // If we've hit our 15 min interval average data and insert into db
          $stmt = $db->prepare("INSERT INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)");
          $stmt->execute(array($row['id'], array_sum($arr) / count($arr), $was + 3600, $res));
          $was = $cached_meter['recorded'];
          unset($arr);
          $arr = array();
        }
        array_push($arr, $cached_meter['value']);
      }
      return;
    }
    elseif ($res === 'month' && $last_recording >= strtotime('-2 months')) {
      $stmt->prepare('SELECT value, recorded FROM meter_data
      WHERE meter_id = ? AND resolution = ? AND recorded >= ?');
      $was = $last_recording;
      $arr = array();
      foreach ($stmt->execute(array($row['id'], 'hour', $last_recording)) as $cached_meter) {
        if ($cached_meter['recorded'] - $was >= 2.628e+6) { // 1 month = 2.628e+6 secs
          // If we've hit our 15 min interval average data and insert into db
          $stmt = $db->prepare("INSERT INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)");
          $stmt->execute(array($row['id'], array_sum($arr) / count($arr), $was + 2.628e+6, $res));
          $was = $cached_meter['recorded'];
          unset($arr);
          $arr = array();
        }
        array_push($arr, $cached_meter['value']);
      }
      return;
    }
    */

    $meter_data = $bos->getMeter($row['url'] . '/data', $res, $last_recording, $time, true);
    $meter_data = json_decode($meter_data, true);
    if ($update_units) {
      // Update the units in case they've changed (only do this for 1 cron job)
      $units = $meter_data['meta']['units']['value']['displayName'];
      $stmt = $db->prepare("UPDATE meters SET units = ? WHERE id = ? LIMIT 1");
      $stmt->execute(array($units, $row['id']));
    }
    $meter_data = $meter_data['data'];
    echo "Raw meter data from BuildingOS:\n";
    print_r($meter_data);
    if (!empty($meter_data)) {
      // Clean up old data
      $stmt = $db->prepare("DELETE FROM meter_data WHERE meter_id = ? AND resolution = ? AND recorded < ?");
      $stmt->execute(array($row['id'], $res, $amount));
      // Delete null data that we're checking again
      $stmt = $db->prepare("DELETE FROM meter_data WHERE meter_id = ? AND resolution = ? AND recorded >= ? AND value IS NULL");
      $stmt->execute(array($row['id'], $res, $last_recording));
      echo "Query ran: DELETE FROM meter_data WHERE meter_id = {$row['id']} AND resolution = {$res} AND recorded < {$amount}\n";
      echo "Query ran: DELETE FROM meter_data WHERE meter_id = {$row['id']} AND resolution = {$res} AND recorded >= {$last_recording}\n";
      echo "Iterating over and inserting data into database:\n";
      $last_value = null;
      $last_recorded = null;
      foreach ($meter_data as $data) { // Insert new data
        $localtime = strtotime($data['localtime']);
        if ($empty || $localtime > $last_recording) {
          $stmt = $db->prepare("INSERT INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)");
          $stmt->execute(array($row['id'], $data['value'], $localtime, $res));
          $last_value = $data['value'];
          $last_recorded = $localtime;
          echo "{$data['value']} @ {$localtime}\n";
        }
      }
      if ($update_current && $last_value !== null) {
        echo "Updating current value to: ";
        var_dump($last_value);
        $stmt = $db->prepare('UPDATE meters SET current = ?, last_updated = ? WHERE id = ? LIMIT 1');
        $stmt->execute(array($last_value, $last_recorded, $row['id']));
      }
    }
    echo "==================================================================\n\n\n\n";
  }
}

?>