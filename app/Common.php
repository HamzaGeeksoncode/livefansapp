<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use File;
use DB;

class Common extends Model
{
    public static function generateUniqueUserId()
    {
        $token =  rand(100000, 999999);

        $first = Common::generateRandomString(3);
        $first .= $token;
        $first .= Common::generateRandomString(3);
        $count = User::where('user_name', $first)->count();

        while ($count >= 1) {

            $token =  rand(100000, 999999);
            $first = Common::generateRandomString(3);
            $first .= $token;
            $first .= Common::generateRandomString(3);
            $count = Common::where('ads_number', $first)->count();
        }

        return $first;
    }

    public static function generateRandomString($length)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function send_push($topic, $title, $message, $plateform = "")
    {
        if ($plateform == 1) {
            $customData =  array("message" => $message);

            $url = 'https://fcm.googleapis.com/fcm/send';

            $api_key = env('FCM_TOKEN');

            // $fields = array (
            //     'registration_ids' => array (
            //         $topic
            //     ),
            //     'data' => $customData
            // );

            $body = $message;
            $notification = array('title' => $title, 'body' => $body, 'sound' => 'default', 'badge' => '1');
            $fields = array('to' => $topic, 'notification' => $notification, 'priority' => 'high');

            $headers = array(
                'Content-Type:application/json',
                'Authorization:key=' . $api_key
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            // print_r(json_encode($fields));
            $result = curl_exec($ch);
            if ($result === FALSE) {
                die('FCM Send Error: ' . curl_error($ch));
            }
            curl_close($ch);

            return $result;
        } else {
            $url = 'https://fcm.googleapis.com/fcm/send';

            $api_key = env('FCM_TOKEN');

            $msg = array('title' => $title, 'body' => $message);

            $message = array(
                "message" => $title,
                "data" => $message,
            );

            $data = array('registration_ids' => array($topic));
            $data['data'] = $message;
            $data['notification'] = $msg;
            $data['notification']['sound'] = "default";

            $headers = array(
                'Content-Type:application/json',
                'Authorization:key=' . $api_key
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            //echo json_encode($data);
            $result = curl_exec($ch);
            if ($result === FALSE) {
                die('FCM Send Error: ' . curl_error($ch));
            }
            curl_close($ch);
            // print_r($result);
            return $result;
        }
    }
}
