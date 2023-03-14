<?php
namespace App\Helper;

use Auth;
use App\Permission;
use Illuminate\Support\Facades\DB;
use App\Helper\GlobalHelper;
use DateTime;
use DateInterval;
use DatePeriod;
use App\User;
use App\Notification;
use URL;
use Twilio;

class GlobalHelper
{
  /**
  * Developed By :
  * Date         :
  * Description  : Time ago
  */
  public static function humanTiming($time){
    $time = time() - strtotime($time); // to get the time since that moment
    $time = ($time<1)? 1 : $time;
    $tokens = array (
      31536000 => 'year',
      2592000 => 'month',
      604800 => 'week',
      86400 => 'day',
      3600 => 'hour',
      60 => 'minute',
      1 => 'second'
    );

    foreach ($tokens as $unit => $text) {
      if ($time < $unit) continue;
      $numberOfUnits = floor($time / $unit);
      return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
    }
  }

  /**
  * Developed By :
  * Date         :
  * Description  : removeNull
  */
  public static function removeNull($array){
    foreach ($array as $key => $value){
      if(is_array($value)){
        $array[$key] = GlobalHelper::removeNull($value);
      }else{
        if (is_null($value))
        $array[$key] = "";
      }
    }
    return $array;
  }

  public static function removeNullMultiArray($model){
    foreach($model as $rsKey => $rs){
      foreach($rs as $key => $value){
        if(is_null($value)){
          $model[$rsKey][$key] = "";
        }
      }
    }
    return $model;
  }

  /**
  * Developed By :
  * Date         :
  * Description  : Get formated date
  */
  public static function getFormattedDate($date)
  {
    if(!empty($date)){
      $date = date_create($date);
      return date_format($date, "d-M-Y");
    }
    else {
      return "";
    }
  }

  /**
  * Developed By :
  * Date         :
  * Description  : Get user by id
  */
  public static function getUserById($id)
  {
    $user = User::where('id','=',$id)
    ->first();
    return $user;
  }


  /**
  * Developed By :
  * Date         :
  * Description  : generateRandomNumber
  */
  public static function generateRandomNumber($length = 10) {
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }

  /**
  * Developed By :
  * Date         :
  * Description  : sentence teaser
  * this function will cut the string by how many words you want
  */
  public static function word_teaser($string, $count){
    $original_string = $string;
    $words = explode(' ', $original_string);

    if (count($words) > $count){
      $words = array_slice($words, 0, $count);
      $string = implode(' ', $words);
    }

    return $string.'...';
  }

  /**
  * Developed By :
  * Date         :
  * Description  : Get user profile image by id
  */
  public static function getUserImageById($id)
  {
    $user = User::select('profile_image')->where('id','=',$id)->first();
    if($user && $user->profile_image){
      return URL::asset('/resources/uploads/profile').'/'.$user->profile_image;
    }else{
      return URL::asset('/resources/uploads/profile/default.jpg');
    }
  }

  /**
  * Description  : Use to convert large positive numbers in to short form like 1K+, 100K+, 199K+, 1M+, 10M+, 1B+ etc
  */
  public static function number_format_short( $n ) {

    if ($n >= 0 && $n < 1000) {
      // 1 - 999
      $n_format = floor($n);
      $suffix = '';
    } else if ($n >= 1000 && $n < 10000) {
      // 1k-999k
      $n_format = floor($n);
      $suffix = '';
    }else if ($n >= 10000 && $n < 1000000) {
      // 1k-999k
      $n_format = floor($n / 1000);
      $suffix = 'K+';
    } else if ($n >= 1000000 && $n < 1000000000) {
      // 1m-999m
      $n_format = floor($n / 1000000);
      $suffix = 'M+';
    } else if ($n >= 1000000000 && $n < 1000000000000) {
      // 1b-999b
      $n_format = floor($n / 1000000000);
      $suffix = 'B+';
    } else if ($n >= 1000000000000) {
      // 1t+
      $n_format = floor($n / 1000000000000);
      $suffix = 'T+';
    }

    return !empty($n_format . $suffix) ? $n_format . $suffix : 0;
  }

  /**
   * Developed By :
  * Date         :
  * Description  : Send FCM For android
  */
  public static function sendFCM($title, $message ,$target = 0, $id = 0){
      //$baseurl="http://".url();
      //FCM api URL
      $url = 'https://fcm.googleapis.com/fcm/send';
      //api_key available in Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key
      $server_key = 'AIzaSyDszVhSGbZ7CfTznlOiDx_U6MJmalBD_wQ';
      $fields = array();

      $fields['data'] = array();
      $fields['data']['body'] = $message;
      $fields['data']['title'] = $title;
      // if(is_array($target)){
      //   $fields['registration_ids'] = $target;
      // }else{
      $fields['to'] = $target;
      // }
      $fields['priority'] = "high";
      $headers = array(
      'Content-Type:application/json',
      'Authorization:key='.$server_key
      );
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
      $result = curl_exec($ch);
      if ($result === FALSE) {
      die('FCM Send Error: ' . curl_error($ch));
      }
      curl_close($ch);
      return $result;
  }

  /**
   * Developed By :
  * Date         :
  * Description  : Send GCM for iphone
  */
  public static function sendGCM($title, $message, $deviceToken, $id = 0 , $app_type){

          // Put your device token here (without spaces):
          //$deviceToken = '6d49d15685a4eb4cee73d1944d8c996182e44d2a6ac0cddc07cdb9507fc0b37b';

          // Put your private key's passphrase here:
          $passphrase = '';

          // Put your alert message here:
          //$message = 'My cQpon push notification!';


          $ctx = stream_context_create();
          stream_context_set_option($ctx, 'ssl', 'local_cert', 'NuWelCom_Push_final.pem');
          stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

          // Open a connection to the APNS server
          if($app_type == 'debug'){
            $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
          }else{
            $fp = stream_socket_client(
            	'ssl://gateway.push.apple.com:2195', $err,
            	$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
          }

          // if (!$fp)
          // 	exit("Failed to connect: $err $errstr" . PHP_EOL);
          //
          // echo 'Connected to APNS' . PHP_EOL;

          // Create the payload body
            $body['aps'] = array(
                'alert'  =>array(
                'title'  => $title,
                'body' 	=> $message,
                'eventId'  => $eventId,
                'eventScreen' 	=> $eventScreen
               ),
                'mutable-content'=> 1,
                'content-available' =>1,
                'category'=> 'Waste',
                'sound' => 'default'
              );
            $body['image'] 	= $image;

          // Encode the payload as JSON
          $payload = json_encode($body);

          // Build the binary notification
          if(strlen($deviceToken) == '64'){
            $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
          }
          //$msg = chr(0) . pack('H*', str_replace(' ', '', sprintf('%u', CRC32($deviceToken)))) . pack('n', strlen($payload)) . $payload;

          // Send it to the server
          $result = fwrite($fp, $msg, strlen($msg));

          // if (!$result)
          // 	echo 'Message not delivered' . PHP_EOL;
          // else
          // 	echo 'Message successfully delivered' . PHP_EOL;

          // Close the connection to the server
          fclose($fp);

  }


  public static function getPermissionByCategory($category){
      $getPermissions = Permission::where("category",$category)->get();
      return $getPermissions;
  }
}
