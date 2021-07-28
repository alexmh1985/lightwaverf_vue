<?php
header('Content-Type: application/json');

class lightwave
{
    public function login() {
        $login = new stdClass();
        $login->email = 'alex@pro1.org';
        $login->password = 'blw0roVets!';
        $login->version = '2.0';
        $login = json_encode($login);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://auth.lightwaverf.com/v2/lightwaverf/autouserlogin/lwapps",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "x-lwrf-appid: ios-01"
            ),
            CURLOPT_POSTFIELDS => $login
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        $response = json_decode($response);
        return $response->tokens->access_token;
    }


    public function fetch($data,$type = 'GET') {
        $access_token = $this->login();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://publicapi.lightwaverf.com/v1".$data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => $type,
            CURLOPT_HTTPHEADER => array(
                "authorization: bearer {$access_token}"
            )
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        $response = json_decode($response);
        return $response;
    }
}


$lw = new lightwave();

$devices = $lw->fetch('/structure/5d8cf584897317722dcad6b4-5d8cf584897317722dcad6b5');
$rooms = $lw->fetch('/rooms');

foreach($devices->devices as &$device){
    foreach($rooms as $room) {
        foreach ($room->featureSets as $room_device) {
            if($room_device === $device->featureSets[0]->featureSetId) {
                $device->room = $room->name;
            }
        }
    }
}

$devices = json_encode($devices);
echo($devices);die;

// echo ($devices);

// foreach($devices->devices as $device) {
//     echo ($device);
// }



//echo($devices);