<?php
require('config.php');
header('Content-Type: application/json');
$uri = strtok($_SERVER["REQUEST_URI"], '?');
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $uri === '/sync') {
    $url = 'https://eplaceapp.nl/api/anonyguest/'.$input['event_id'].'/evite-2019-KJDSGHAGD/';
    $to_be_shared = $db->Select('connector', '*', array('event_id' => $input['event_id'], '!updated_at' => new DatabaseFunc('proccessed_at')));
    foreach($to_be_shared as $connector) {
        $ch = curl_init($url);

        $data = array(
            "GuestId" => $connector['id'],
            "FirstName" => $connector['firstname'],
            "MiddleName" => $connector['middlename'],
            "LastName" =>  $connector['lastname'],
            "Sex" =>  $connector['sex'],
            "CompanyName" =>  $connector['companyname'],
            "JobFunction" => $connector['jobfunction'],
            "NamePartner" =>  $connector['namepartner'],
            "StreetPostbus" =>  $connector['streetpostbus'],
            "HouseNumber" =>  $connector['housenumber'],
            "PostalCode" => $connector['postalcode'],
            "City" =>  $connector['city'],
            "Country" => $connector['country'],
            "EmailAddress" => $connector['emailaddress'],
            "Remarks" =>  $connector['remarks'],
        );
        $payload = json_encode($data);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);                                                                   
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);
    }
    $data = (json_decode(file_get_contents($url), true));
    $mapper = [
        'GuestId' => 'id',
        'AdditionalJsonData' => 'additionaljsondata',
        'AmountOfPersons' => 'amountofpersons',
        'City' => 'city',
        'CompanyName' => 'companyname',
        'Country' => 'country',
        'DepartmentGroup' => 'departmentgroup',
        'EmailAddress' => 'emailaddress',
        'FirstName' => 'firstname',
        'GuestOfAttendee' => 'guestofattendee',
        'HouseNumber' => 'housenumber',
        'JobFunction' => 'jobfunction',
        'LastName' => 'lastname',
        'MiddleName'=>'middlename',
        'NamePartner'=>'namepartner',
        'PostalCode'=>'postalcode',
        'Priority' => 'priority',
        'Remarks' => 'remarks',
        'RowNumber' => 'rownumber',
        'SeatNumber' => 'seatnumber',
        'Section' => 'section',
        'Sex' => 'sex',
        'StreetPostbus' => 'streetpostbus'
    ];
    foreach ($data['Guests'] as $guest) {
        $dbGuest = $db->SelectOne('connector', '*', ['id' => $guest['GuestId']]);
        $newGuest = [];
        foreach($guest as $k => $v) {
            if(array_key_exists($k, $mapper)) {
                $newGuest[$mapper[$k]] = $v;
            }
        }
        $newGuest['event_id'] = $input['event_id'];
        $newGuest['proccessed_at'] = new DatabaseFunc('Now()');
        if ($dbGuest) {
            $db->Update('connector', ['id' => $guest['GuestId']], $newGuest);
        } else {
            $db->Insert('connector', $newGuest);
        }
    }
    echo json_encode($data);
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