<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Util\GlobalState;

class GlobalFunction extends Model
{
    use HasFactory;

    // public static function sendPushToUser($title, $message, $token)
    // {
    //     $url = 'https://fcm.googleapis.com/fcm/send';
    //     $api_key = env('FCMKEY');
    //     $notificationArray = array('title' => $title, 'body' => $message, 'sound' => 'default', 'badge' => '1');

    //     $fields = array('to' => "/token/" . $token, 'notification' => $notificationArray, 'priority' => 'high');
    //     $headers = array(
    //         'Content-Type:application/json',
    //         'Authorization:key=' . $api_key
    //     );
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_POST, true);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    //     // print_r(json_encode($fields));
    //     $result = curl_exec($ch);
    //     if ($result === FALSE) {
    //         die('FCM Send Error: ' . curl_error($ch));
    //         Log::debug(curl_error($ch));
    //     }
    //     curl_close($ch);

    //     if ($result) {
    //         $response['status'] = true;
    //         $response['message'] = 'Notification sent successfully !';
    //     } else {
    //         $response['status'] = false;
    //         $response['message'] = 'Something Went Wrong !';
    //     }
    //     // echo json_encode($response);
    // }

    public static function createMediaUrl($media)
    {
        $url = env('ITEM_BASE_URL') . $media;
        return $url;
    }

    public static function uploadFilToS3($file)
    {
        $s3 = Storage::disk('s3');
        $fileName = time() . $file->getClientOriginalName();
        $fileName = str_replace(array(' ', ':'), '_', $fileName);
        $destinationPath = env('DEFAULT_IMAGE_PATH');
        $filePath = $destinationPath . $fileName;
        $result =  $s3->put($filePath, file_get_contents($file), 'public-read');
        return $fileName;
    }

    public static function cleanString($string)
    {

        return  str_replace(array('<', '>', '{', '}', '[', ']', '`'), '', $string);
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
}
