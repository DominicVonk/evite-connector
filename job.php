<?php

require('config.php');

$events = $db->Select('events', '*', ['enabled' => 1]);

foreach($events as $event) {
    $ch = curl_init(getenv('SELF_URL') . '/sync?code=' . getenv('SELF_CODE'));

    $data = array(
        "event_id" => $event['event_id']
    );
    $payload = json_encode($data);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);                                                                   
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);

    curl_close($ch);
}