<?php
/**
 * Created by IntelliJ IDEA.
 * User: nayab
 * Date: 22-Nov-17
 * Time: 3:44 PM
 */

namespace Utils;


class PushNotifications
{
    // (Android)API access key from Google API's Console.
    private static $FCM_API_ACCESS_KEY = 'AIzaSyDy1ZDioUBR3uDqTQux-rI15d5Oqaa45y4';
//    // (iOS) Private key's passphrase.
//    private static $passphrase = 'taskplannerpns';

    public function __construct() {
        exit('Init function is not allowed');
    }


    public static function sendNotification()
    {

    }

    public static function sendPush($message, $object_id, $object_type, $device_tokens)
    {

	    //$device_tokens[] = "cyxPS6I0Hdg:APA91bFWxLTO0UDUL6YYWb_J5hGkUNbkRYSakHRwnt47hOqsXadl6MxuVvCeHLmC1BvNs_SKCqAAnuKjlJnRUi5_hPbn0IqpMavY-trEDkcKIuQKxV41eklz9Wf9Xd54zcnZO4S5h2vO";
	   // $device_tokens[] = "dvpbWp9Pc90:APA91bG0TMvrFUQ2dvzK6-_PVR3jY6C-bdae9H3Wqu2l8B9p4Xkg_Q4wJD5lLmMkR8ygyFv_Lj0YjTt0lsub0ha566vOVjgC8eIwvds96-dhCufoIhe8C8-0Z51YhnkWhLuav_E9U7KA";


        //return $gcm_ios_mobile_reg_key; exit;
        //$gcm_ios_mobile_reg_key[] = "dBE78x4bEng:APA91bEhDemOdSRRdBaKmX_OqHTD-T7htIywKiQPQNrrzJ2N-QaRFSYWCf7iX9w_5quurdyZZKxKGnoNNC3-KjrMwr0HmSzmpHet4OBTCOH1JVgBgoC6qsuWDVe2VQWToGFML6FZe_Uc";

	    $authkey = self::$FCM_API_ACCESS_KEY;
        $fields = array(
            "registration_ids" => $device_tokens, //1000 per request logic is pending
            "priority" => "high",
            "data" => array(
                "Message" => $message,
                "object_id" => $object_id,
                "object_type" => $object_type,
            ),
            'notification' => [
                "body" => $message,
                "title" => "Task Planer",
                "sound" => "default",
                "click_action" => "Slient_Task"
            ]
        );

        $url = 'https://fcm.googleapis.com/fcm/send'; //note: its different than android.

        $headers = array(
            'Authorization: key='.$authkey,
            'Content-Type: application/json'
        );

        return self::useCurl($url, $headers, $fields);
    }

	private function useCurl($url, $headers, $fields = null)
	{
		// Open connection
		$ch = curl_init();
		if ($url) {
			// Set the url, number of POST vars, POST data
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Disabling SSL Certificate support temporarly
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			if ($fields) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
			}

			// Execute post
			$result = curl_exec($ch);
			if ($result === FALSE) {
				die('Curl failed: ' . curl_error($ch));
			}

			// Close connection
			curl_close($ch);
			return $result;
		}
	}

    // Sends Push notification for Android users
//    public function android($data, $reg_id) {
//        $url = 'https://android.googleapis.com/gcm/send';
//        $message = array(
//            'title' => $data['mtitle'],
//            'message' => $data['mdesc'],
//            'subtitle' => '',
//            'tickerText' => '',
//            'msgcnt' => 1,
//            'vibrate' => 1
//        );
//        $headers = array(
//            'Authorization: key=' .self::$API_ACCESS_KEY,
//            'Content-Type: application/json'
//        );
//        $fields = array(
//            'registration_ids' => array($reg_id),
//            'data' => $message,
//        );
//        return self::useCurl($url, json_encode($headers), json_encode($fields));
//    }

    // Sends Push notification for iOS users
//    public function iOS($data, $devicetoken) {
//        $deviceToken = $devicetoken;
//        $ctx = stream_context_create();
//        // ck.pem is your certificate file
//        stream_context_set_option($ctx, 'ssl', 'local_cert', 'ck.pem');
//        stream_context_set_option($ctx, 'ssl', 'passphrase', self::$passphrase);
//        // Open a connection to the APNS server
//        $fp = stream_socket_client(
//            'ssl://gateway.sandbox.push.apple.com:2195', $err,
//            $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
//        if (!$fp)
//            exit("Failed to connect: $err $errstr" . PHP_EOL);
//        // Create the payload body
//        $body['aps'] = array(
//            'alert' => array(
//                'title' => $data['mtitle'],
//                'body' => $data['mdesc'],
//            ),
//            'sound' => 'default'
//        );
//        // Encode the payload as JSON
//        $payload = json_encode($body);
//        // Build the binary notification
//        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
//        // Send it to the server
//        $result = fwrite($fp, $msg, strlen($msg));
//
//        // Close the connection to the server
//        fclose($fp);
//        if (!$result) {
//            return 'Message not delivered' . PHP_EOL;
//        } else {
//            return 'Message successfully delivered' . PHP_EOL;
//        }
//    }
}