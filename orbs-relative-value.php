<?php

// db connection
require '/var/www/repos/includes/db.php';
// require './db.php';

// declaring variables
$datahubIdArr = [];
$relativeValueArr = [];

// getting data from orbs table
foreach ($db->query('select datahub_elec_id as datahub_id, elec_rvid as relative_value_id from orbs where disabled = 0 union select datahub_water_id as datahub_id, water_rvid as relative_value_id from orbs where disabled = 0 order by `datahub_id` ASC') as $row) {
    // checking if datahub is not null
    if(!is_null(($row['datahub_id'])))
    {   
        if(!in_array($row['datahub_id'],$datahubIdArr)){
            $datahubIdArr[] = $row['datahub_id'];
        }

        // creating separate array as single variables can be related to multiple relative value primary keys
        $relativeValueArr[$row['datahub_id']][] = $row['relative_value_id'];
    }
}

if(count($datahubIdArr) > 0){

    // creating id's chunk
    $datahubIdArrChunk  = array_chunk($datahubIdArr,20);
    
    foreach($datahubIdArrChunk as $datahubIds){
        
        // comma separated id for fetching id
        $commaSeparatedIds = implode(",", $datahubIds);
        
        // call get data function to get updated related value
        $relativeValues = getData($commaSeparatedIds);

        foreach($relativeValues as $key => $value){
            
            if(!is_null($value)){

                foreach($relativeValueArr[$key] as $relativeValueId){
                    
                    try{
                        // storing new relative valuw
                        $stmt = $db->prepare('UPDATE relative_values SET relative_value = ?, last_updated = ? WHERE id = ?');
                        $stmt->execute([$value, strtotime("now"), $relativeValueId]);

                    }catch(Exception $e){
                        echo '<pre>';
                        print_r($row['relative_value_id']);
                        print_r($e);
                        echo '</pre>';
                    }
                }
            }
        }
    }
}


/** funtion to fetch relative value for a particular datahub variable/meter */
function getData($datahubIds){
    
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL,'https://oberlin.communityhub.cloud/api/data-hub-v2/relative-value/orbs?var_id='.$datahubIds);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $response = curl_exec($ch);
  $result = json_decode($response);
  curl_close($ch); // Close the connection

  return $result;  
}
?>
