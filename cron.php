<?php
/**
 * Cron job to access BuildingOS API and update meter data in database.
 * This file has the cron() function. Look at ~/scripts/jobs/ for their usage.
 *
 * The idea here is that requesting large amounts of data from the API is too slow so data is cached in MySQL
 * The first time the cron job runs all data should be fetched form the API. After that, the jobs at the various intervals get the most recent reading (which, aside from the minutely job, ~~are all calculated with existing data~~) <-- actually not yet, still working on this
 *
 * The job at the 1 minute interval is for collecting minute resolution meter data (going back 2 hours) and updating meters current values
 * The job at the 15 minute interval is for collecting quarterhour resolution meter data (going back 2 weeks)
 * The job at the 1 hour interval is for collecting hour resolution meter meter data (going back 2 months) and updating the relative_values table
 * The job at the 1 month interval is for collecting month resolution meter data (going back 2 years)
 *
 * @author Tim Robert-Fitzgerald June 2016
 */
function cron($db, $bos, $meter, $res, $amount, $update_current = false, $update_units = false, $update_relative_value = false) {
  $time = time();
  foreach ($db->query('SELECT id, url FROM meters WHERE (num_using > 0 OR for_orb = 1 OR orb_server > 0) AND source = \'buildingos\' ORDER BY RAND()') as $row) { // Get all the meters that were manually put on the cron job (i.e. num_using), meters used by Oberlin's orbs (i.e. for_orb), and meters used by Jeremy's orbs app (i.e. orb_server)
    echo "Fetching meter #{$row['id']}\n";
    // Check to see what the last recorded value is
    // I just added 'AND value IS NOT NULL' because sometimes BuildingOS returns null data and later fixes it? ...weird
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

    /* TODO: Implement this
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
          if ($data['value'] !== null) {
            $last_value = $data['value'];
            $last_recorded = $localtime;
          }
          echo "{$data['value']} @ {$localtime}\n";
        }
      }
      if ($update_current && $last_value !== null) { // Update meters table
        $stmt = $db->prepare('UPDATE meters SET current = ?, last_updated = ? WHERE id = ? LIMIT 1');
        $stmt->execute(array($last_value, $last_recorded, $row['id']));
      }
      if ($update_relative_value && $last_value !== null) { // Update relative_values table
        $stmt = $db->prepare('SELECT id, grouping FROM relative_values WHERE meter_id = ?');
        $stmt->execute(array($row['id']));
        $day_of_week = date('w') + 1; // https://dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_dayofweek
        foreach ($stmt->fetchAll() as $rv_row) {
          // Example JSON: [{"days":[1,2,3,4,5],"npoints":8},{"days":[1,7],"npoints":5}]
          foreach (json_decode($rv_row['grouping'], true) as $group) {
            if (in_array($day_of_week, $group['days'])) {
              $days = $group['days'];
              if (array_key_exists('npoints', $group)) {
                $amount = intval($group['npoints']);
                $days = implode(',', array_map('intval', $days)); // prevent sql injection with intval as we're concatenating directly into query
                $stmt = $db->prepare(
                  "SELECT value FROM meter_data
                  WHERE meter_id = ? AND value IS NOT NULL AND resolution = ?
                  AND HOUR(FROM_UNIXTIME(recorded)) = HOUR(NOW())
                  AND DAYOFWEEK(FROM_UNIXTIME(recorded)) IN ({$days})
                  ORDER BY recorded DESC LIMIT " . $amount); // ORDER BY recorded DESC is needed because we're trying to extract the most recent $amount points
                $stmt->execute(array($row['id'], 'hour'));
                $relative_value = $meter->relativeValue(array_map('floatval', array_column($stmt->fetchAll(), 'value')), $last_value);
              } else if (array_key_exists('start', $group)) {
                $amount = intval($group['start']);
                $days = implode(',', array_map('intval', $days));
                $stmt = $db->prepare(
                  "SELECT value, recorded FROM meter_data
                  WHERE meter_id = ? AND value IS NOT NULL
                  AND recorded > ? AND recorded < ? AND resolution = ?
                  AND HOUR(FROM_UNIXTIME(recorded)) = HOUR(NOW())
                  AND DAYOFWEEK(FROM_UNIXTIME(recorded)) IN ({$days})
                  ORDER BY value ASC"); // ORDER BY value ASC is efficient here because the relativeValue() method will sort the data like this (and there's no need to sort by recorded -- the amount of data is determined by $amount, which is a unix timestamp representing when the data should start)
                $stmt->execute(array($row['id'], $amount, time(), 'hour'));
                $relative_value = $meter->relativeValue($stmt->fetchAll(), $last_value);
              }
              $stmt = $db->prepare('UPDATE relative_values SET relative_value = ? WHERE id = ?');
              $stmt->execute(array(round($relative_value), $rv_row['id']));
              break;
            } // if
          } // foreach
        } // foreach
      } // if $update_relative_value
    } // if !empty($meter_data)
    echo "==================================================================\n\n\n\n";
  }
}

?>