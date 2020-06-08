<?php
require('config.php');
header('Content-Type: application/json');
$uri = strtok($_SERVER["REQUEST_URI"], '?');
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $uri === '/sync') {
    $url = 'https://eplaceapp.nl/api/anonyguest/'.$input['event_id'].'/evite-2019-KJDSGHAGD/';
    $to_be_shared = $db->Select('connector', '*', array('event_id' => $input['event_id']));
    
    $to_be_shared = array_filter($to_be_shared, function($e) {
        return $e['updated_at'] !== $e['proccessed_at'] || !$e['updated_at'];
    });
    foreach($to_be_shared as $connector) {
        $ch = curl_init($url);

        $data = array(
            "GuestId" => $connector['seat_id'],
            "FirstName" => $connector['firstname']?:'',
            "MiddleName" => $connector['middlename']?:'',
            "LastName" =>  $connector['lastname']?:'',
            "Sex" =>  $connector['sex']?:'',
            "CompanyName" =>  $connector['companyname']?:'',
            "JobFunction" => $connector['jobfunction']?:'',
            "NamePartner" =>  $connector['namepartner']?:'',
            "StreetPostbus" =>  $connector['streetpostbus']?:'',
            "HouseNumber" =>  $connector['housenumber']?:'',
            "PostalCode" => $connector['postalcode']?:'',
            "City" =>  $connector['city']?:'',
            "Country" => $connector['country']?:'',
            "EmailAddress" => $connector['emailaddress']?:'',
            "Remarks" =>  $connector['remarks']?:'',
        );
        $newGuest = [];
        
        $newGuest['proccessed_at'] = new DatabaseFunc('Now()');
        $db->Update('connector', ['seat_id' => $connector['seat_id']], $newGuest);
    

        $payload = json_encode($data);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);                                                                   
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);
    }
    
    //echo json_encode($data);
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event = $db->SelectOne('events', 'event_id', ['event_id' => $input['event_id']]);
    if (!$event) {
        $db->Insert('events', ['event_id' => $input['event_id'], 'enabled' => 1]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $uri === '/disable') {
    $db->Update('events', ['event_id' => $input['event_id']], ['enabled' => 0]);
}
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $uri === '/enable') {
    $db->Update('events',  ['event_id' => $input['event_id']], ['enabled' => 1]);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $db->Delete('events', ['event_id' => $input['event_id']]);
    $db->Delete('connector', ['event_id' => $input['event_id']]);
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(array_map(function($e) { unset($e['id']); return $e; }, $db->Select('events', '*', ['enabled' => 1])));
}