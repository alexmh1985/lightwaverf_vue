<?php
header('Content-Type: application/json');

class lightwave
{
    public function login() {
        $login = new stdClass();
        /* Lightwave RF Login details */
        $login->email = 'alex@pro1.org';
        $login->password = 'blw0roVets!';
        $login->version = '2.0';
        /* Lightwave RF Login details */
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
        if($response === 'Not found.') {
            return ['result'=>0, 'response'=>'ERROR! Unable to access LightwaveRF API, check your credentials'];
        }
        $response = json_decode($response);      
        file_put_contents('access_token.token',$response->tokens->access_token);
        return ['result'=>1, 'response'=>'access token token updated'];
    }

    public function fetch($data,$type = 'GET') {
        $access_token = file_get_contents('access_token.token');
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
        if((isset($response->message) && $response->message === 'Unauthorized')) {
            return ['result'=>0, 'response'=>'Unauthorized'];
        }
        return ['result'=>1, 'response'=>'response returned', 'data'=>$response];
    }
}

$lw = new lightwave();

if(!file_exists('access_token.token') || strlen(file_get_contents('access_token.token')) < 10) { // no token exists
    $login = $lw->login();
    if(!$login['result']) {
        echo(json_encode($login));die;
    }
}

$rooms = $lw->fetch('/rooms'); // fetch all rooms
if(!$rooms['result']) {
    if($rooms['response'] === 'Unauthorized') { // token expired?
        $login = $lw->login();
        if(!$login['result']) {
            echo(json_encode($login));die;
        }
        $rooms = $lw->fetch('/rooms');
    } else {
        echo(json_encode($rooms));die;
        return;
    }
}
$devices = $lw->fetch('/structure/5d8cf584897317722dcad6b4-5d8cf584897317722dcad6b5'); // fetch all devices
if(!$devices['result']) {
    echo(json_encode($devices));die;
    return;
}

$all_rooms = [];
foreach($devices['data']->devices as &$device){ // add devices to rooms
    foreach($rooms['data'] as $room) {
        foreach ($room->featureSets as $room_device) {
            if($room_device === $device->featureSets[0]->featureSetId) {
                $room_name = strtolower(str_replace(' ', '_', $room->name));
                $device_name = strtolower(str_replace(' ', '_', $device->name));
                $all_rooms[$room_name][$device_name] = $device;
            }
        }
    }
}

echo(json_encode($all_rooms));die;