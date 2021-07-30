<?php
header('Content-Type: application/json');

class lightwave
{
    public function login() {
        $login = new stdClass();
        ####################### Lightwave RF Login details #######################
        $login->email = 'someuser@email.com';
        $login->password = 'some_passsword';
        ####################### Lightwave RF Login details #######################
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
        if($response === 'Not found.') {
            return ['result'=>0, 'response'=>'ERROR! Unable to access LightwaveRF API, check your credentials'];
        }
        $response = json_decode($response);      
        file_put_contents('access_token.token',$response->tokens->access_token);
        return ['result'=>1, 'response'=>'access token token updated'];
    }

    public function fetch($data,$type = 'GET', $body = '') {
        $access_token = file_get_contents('access_token.token');
        $content_type = "";
        if(!empty($body)) {
            $body = json_encode($body);
            $content_type = "Content-Type: application/json";
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://publicapi.lightwaverf.com/v1".$data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => $type,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array(
                "authorization: bearer {$access_token}",
                $content_type
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

$type = $_REQUEST['request'];
if($type === 'getdevices') {
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


    $all_features = new stdClass();
    $all_features->features = [];
    $all_features_with_info = [];
    foreach($devices['data']->devices as &$device){ // add devices to rooms
        foreach($rooms['data'] as $room) {
            foreach ($room->featureSets as $room_device) {
                if($room_device === $device->featureSets[0]->featureSetId) {
                    foreach($device->featureSets as $individual_switches) {
                        foreach($individual_switches->features as $feature) {
                            if($feature->type === 'switch' || $feature->type === 'dimLevel') {
                                $this_feature = new stdClass();
                                $this_feature->featureId = $feature->featureId;
                                $all_features->features[] = $this_feature;
                                $room_name = strtolower(str_replace(' ', '_', $room->name));
                                $device_name = strtolower(str_replace(' ', '_', $device->name));
                                $feature_name = strtolower(str_replace(' ', '_', $individual_switches->name));
                                $all_features_with_info[$feature->featureId]['room_name'] = $room_name;
                                $all_features_with_info[$feature->featureId]['device_name'] = $device_name;
                                $all_features_with_info[$feature->featureId]['feature_name'] = $feature_name;
                                $all_features_with_info[$feature->featureId]['feature_type'] = $feature->type;
                            }
                        }
                    }
                }
            }
        }
    }
    $current_device_status = $lw->fetch('/features/read','POST',$all_features);

    $all_rooms = [];
    foreach($all_features_with_info as $feature_index => $feature){
        foreach($current_device_status['data'] as $status_index => $item_status){
            if($feature_index === $status_index) {
                $ft_status['id'] = $feature_index;
                $ft_status['status'] = $item_status;
                $all_rooms[$feature['room_name']][$feature['device_name']][$feature['feature_name']][$feature['feature_type']] = $ft_status;
            }
        }
    }
    $response = ['result'=>1, 'response'=>'all rooms returned', 'rooms'=>$all_rooms];
    echo(json_encode($response));die;
}

if($type === 'control') {
    $feature = $_GET['feature'];
    $body = new stdClass();
    $body->value = $_GET['status'];
    $control = $lw->fetch('/feature/'.$feature,'POST',$body);
    echo(json_encode($control));die;
}