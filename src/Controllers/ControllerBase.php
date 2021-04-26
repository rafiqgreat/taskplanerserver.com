<?php
/**
 * Created by IntelliJ IDEA.
 * User: nayab
 * Date: 22-Sep-17
 * Time: 8:56 PM
 */
namespace Controllers;

use \Constants\Messages;
use \Constants\Statuses;
use \Utils\CommonUtils;
use \Utils\JsonUtil;
use \Constants\NotifyMessages as notifyMsg;
use \Utils\PushNotifications;
use Constants\ParamKeys;

class ControllerBase extends JsonUtil
{
    private $contentType = 'application/json;charset=utf-8';

    protected $post_body;
    protected $qb_token;

    public function getParams($request) {
        $params = array();
        $httpType = $request->getMethod();
        if ($httpType == 'POST') { $params = $request->getParsedBody(); }
        else if ($httpType == 'GET') { $params = $request->getQueryParams(); }
        foreach($params as $key => $value){
            if ($value == '') {
                return $key;
            }
        }
        return "";
    }

    private function responseBody($response, $responseBody) {
        $body = $response->getBody();
        $body->write($responseBody);
        return $response->withHeader('Content-Type', $this->contentType)->withBody($body);
    }

    public function sendHttpResponseSuccess($response, $data) {
        return $this->responseBody($response, $this->getJsonObject(Statuses::SUCCESS, Messages::MSG_REQUEST_SUCCESSFUL, $data));
    }

    public function sendHttpResponseMessage($response, $message) {
        return $this->responseBody($response, $this->getJsonObject(Statuses::SUCCESS, $message));
    }

    public function sendHTTPResponseError($response, $message) {
        return $this->responseBody($response, $this->getJsonObject(Statuses::ERROR, $message));
    }

    public function sendConnectionError($response) {
        return $this->responseBody($response, $this->getJsonObject(Statuses::ERROR, Messages::MSG_ERR_DB_CONNECTION));
    }

    public function sendInvalidAuthCode($response) {
        return $this->responseBody($response, $this->getJsonObject(Statuses::ERROR, Messages::MSG_ERR_AUTH_CODE));
    }

    public function sendInvalidParamError($response) {
        return $this->responseBody($response, $this->getJsonObject(Statuses::ERROR, Messages::MSG_ERR_HTTP_PARAMS));
    }

    public function sendExceptionMessage($response, $exception) {
        return $this->responseBody($response, $this->getJsonObject(Statuses::EXCEPTION, $exception));
    }

    public function sendJsonResponse($response, $status, $msg=false, $data=false, $status_code=200){
        if($data && $status == 'SUCCESS') {
            $resp['STATUS'] = $status;
            $resp['DATA'] = $data;
            return $response->withJson($resp, $status_code);
        }else if($status == "SUCCESS" && $data == false){
            $resp['STATUS'] = $status;
            return $response->withJson($resp, $status_code);
        }else if($status == "ERROR" && $data == false){
            $resp['STATUS'] = $status;
            $resp['MSG'] = $msg;
            return $response->withJson($resp, $status_code);
        }
    }

    public function sendNotification($senderId, $receiverId, $objectId, $objectType, $notificationType, $deviceTokens, $objectTitle=false)
    {
	    $notification = $this->notificationMsgMaping($notificationType, $senderId, $objectId, 'task', $objectTitle);

	    if ($this->db) {
		    $this->db->beginTransaction();
		    $statementTask = $this->db->prepare('INSERT INTO cor_notifications (NOTIFICATION, NOTIFICATION_TYPE, SENDER_ID, RECEIVER_ID, OBJECT_ID, OBJECT_TYPE, CREATED_AT) 
                                                                    VALUES(:notification, :notification_type, :senderId, :receiverId, :objectId, :objectType, :createdAt)');
		    $statementTask->bindParam(":notification", $notification);
		    $statementTask->bindParam(":notification_type", $notificationType);
		    $statementTask->bindParam(":senderId", $senderId);
		    $statementTask->bindParam(":receiverId", $receiverId);
		    $statementTask->bindParam(":objectId", $objectId);
		    $statementTask->bindParam(":objectType", $objectType);
			// rafiq changed file for strict error
			$curtimestamp = time();
		    $statementTask->bindParam(":createdAt", $curtimestamp);
		   
		    $statementTask->execute();
		    $lastInsertId = $this->db->lastInsertId();
		    if ($this->db->commit()) {
			    // send notification

                if($this->checkTaskMuteStatus($objectId, $receiverId)) {
                    return PushNotifications::sendPush($notification, $objectId, $objectType, $deviceTokens);
                }
		    }
	    } else {
		   // return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
	    }
    }

    // map notification type with its specific message
    // map notification type with its specific message
    public function notificationMsgMaping($type, $subjectId, $objectId, $objectType, $objectTitle)
    {
        if($type){
            $notifyMsg = new notifyMsg();
            $raw_msg =  $notifyMsg->getNotificationText($type);
            if($raw_msg){
                if($type == 'SHIPMENT_APPROVED') {
                    $subject = $this->getObjectNameById($objectId, 'shipment')['title'] . ' shipment';
                    $object = '';
                } else if ($type != 'STATUS_CHANGED') {
                    if ($type == 'TASK_REMEMBER') {
                        $subject = strtoupper($this->getObjectNameById($objectId, $objectType)['title']);
                    } else if ($type == 'TASK_OVERDUE') {
                        $subject = strtoupper($this->getObjectNameById($objectId, $objectType)['title']);
                    } else if($type == 'SHIPMENT_STATUS_CHANGED') {
                        $subject = $this->getObjectNameById($objectId, 'shipment')['title'] . ' shipment';
                    }  else {
                        $subject = $this->getSubjectNameById($subjectId)['title'];
                    }

                    $object = $objectTitle;

                    if ($object != false && $type != 'TASK_OVERDUE' && $type != 'TASK_REMEMBER') {
                        $object = $this->getObjectNameById($objectId, $objectType)['title'];
                    }

                } else if($type != 'NEW_SHIPMENT_ADDED' && $type != 'STATUS_CHANGED') {
                    $subject = $this->getSubjectNameById($subjectId)['title'];
                    $object = '';
                } else {
                    $subject = $this->getTaskByID($objectId)['TASK_TITLE'];
                    $object = $objectTitle;
                }

                $notification = str_replace([":subject", ":object"], [$subject, $object], $raw_msg);
                return $notification;
            }

            return "";
        }
    }

    public function getDeviceToken($userId) {
	    $taskSql = "select DEVICE_TOKEN as device_token from ptf_user_details where USER_ID = :userId";
	    $tasksStatement = $this->db->prepare($taskSql);
	    $tasksStatement->bindParam(":userId", $userId);
	    $tasksStatement->execute();
	    $row = $tasksStatement->fetch(\PDO::FETCH_ASSOC);
	    return $row['device_token'];
    }

    public function getSubjectNameById($subjectId){
	    $taskSql = "select FULL_NAME as title from ptf_user_details where USER_ID = :subjectId";
	    $tasksStatement = $this->db->prepare($taskSql);
	    $tasksStatement->bindParam(":subjectId", $subjectId);
	    $tasksStatement->execute();
	    return $tasksStatement->fetch(\PDO::FETCH_ASSOC);
    }

	public function getObjectNameById($objectId, $objectType){
    	if($objectType == 'task') {
		    $taskSql = "select TASK_TITLE as title from cor_project_tasks where TASK_ID = :objectId";
		    $tasksStatement = $this->db->prepare($taskSql);
		    $tasksStatement->bindParam(":objectId", $objectId);
		    $tasksStatement->execute();
		    return $tasksStatement->fetch(\PDO::FETCH_ASSOC);

	    } else if($objectType == 'project') {
		    $taskSql = "select PROJECT_NAME as title from cor_project_details where PROJECT_ID = :objectId";
		    $tasksStatement = $this->db->prepare($taskSql);
		    $tasksStatement->bindParam(":objectId", $objectId);
		    $tasksStatement->execute();
		    return $tasksStatement->fetch(\PDO::FETCH_ASSOC);

	    } else if($objectType == 'shipment') {
            $taskSql = "select CUSTOMER_NAME as title from cor_shipments where SHIPMENT_ID = :objectId";
            $tasksStatement = $this->db->prepare($taskSql);
            $tasksStatement->bindParam(":objectId", $objectId);
            $tasksStatement->execute();
            return $tasksStatement->fetch(\PDO::FETCH_ASSOC);
        }
	}

    public function getTaskById($objectId){
        $taskSql = "select TASK_TITLE,TASK_STATUS from cor_project_tasks where TASK_ID = :objectId";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":objectId", $objectId);
        $tasksStatement->execute();
        return $tasksStatement->fetch(\PDO::FETCH_ASSOC);
    }
	
	public function createPersonalProject($userId){
        $projectName = "Personal Task";
        $description = "This is for my personal tasks";
        $dueDate = date('Y-m-d H:i:s');
    	$this->db->beginTransaction();
        $statementProject = $this->db->prepare('INSERT INTO ' . $this->getProjects() . ' (PROJECT_NAME, PROJECT_DESCRIPTION, CREATOR_ID, DUE_DATE) VALUES(:projectName, :description, :userId, :dueDate)');
        $statementProject->bindParam(":projectName", $projectName, \PDO::PARAM_STR);
        $statementProject->bindParam(":description", $description, \PDO::PARAM_STR);
        $statementProject->bindParam(":userId", $userId, \PDO::PARAM_STR);
        $statementProject->bindParam(":dueDate", $dueDate, \PDO::PARAM_STR);
        $statementProject->execute();
        $projectId = $this->db->lastInsertId();
        $this->db->commit();
        return $projectId;
	}

	public function getCurrentTimestamp($hours = 0, $minutes = 0) {
        $date = new \DateTime();

        if($hours > 0 && $minutes > 0) {
            $date->add(new \DateInterval('PT' . $hours . 'H' . $minutes . 'M'));
        } else if ($hours == 0 && $minutes > 0) {
            $date->add(new \DateInterval('PT' . $minutes . 'M'));
        }

        return $date->getTimestamp();
    }

    public function convertToTimestamp($date) {
        $date = new \DateTime($date);

        return $date->getTimestamp();
    }
	 public function convertToTimestampRafiq($date) {
		 return $timestamp = strtotime($date);        
    }
	
	
	//date 

    public function convertTimestampToDate($t) {
        $date = new \DateTime();
        $date->setTimestamp($t);

        return $date->format('Y-m-d H:i:00');
    }

    public function _qbsession() {
        // Generate signature
        $nonce              = rand();
        $timestamp          = time(); // time() method must return current timestamp in UTC but seems like hi is return timestamp in current time zone
        $signature_string   = "application_id=" . ParamKeys::APPLICATION_ID . "&auth_key=" . ParamKeys::AUTH_KEY . "&nonce=" . $nonce . "&timestamp=" . $timestamp . "&user[login]=" . ParamKeys::USER_LOGIN . "&user[password]=" . ParamKeys::USER_PASSWORD;
        $signature          = hash_hmac('sha1', $signature_string , ParamKeys::AUTH_SECRET);

        // Build post body
        $this->post_body = http_build_query(array(
            'application_id'    => ParamKeys::APPLICATION_ID,
            'auth_key'          => ParamKeys::AUTH_KEY,
            'timestamp'         => $timestamp,
            'nonce'             => $nonce,
            'signature'         => $signature,
            'user[login]'       => ParamKeys::USER_LOGIN,
            'user[password]'    => ParamKeys::USER_PASSWORD
        ));

        return $this->execute_curl(ParamKeys::QB_PATH_SESSION);

    }

    public function _qbsignup($params) {

        $mobile =  str_replace('+','', $params[ParamKeys::MOBILE_NUMBER]);

        $data = array(
            'user' => array(
                'login'       => $mobile,
                'password'    => $mobile,
                'email'       => $mobile . '@gmail.com',
                'full_name'   => $params[ParamKeys::FULL_NAME],
                'phone'       => $mobile
            )
        );

        $this->post_body = json_encode($data);

        return $this->execute_curl(ParamKeys::QB_USER_SIGNUP);
    }

    public function _qbGetUserByLogin($params) {

        $mobile = str_replace('+','', $params);

        return $this->execute_curl_get(ParamKeys::QB_GET_USER_BY_LOGIN, $mobile);
    }

    public function _createQbGroup($type, $names, $user_ids) {
        $names_str = implode(',',$names);
        $users_str = implode(',',$user_ids);

        $data = array(
            'type'          => $type,
            'name'          => $names_str,
            'occupants_ids' => $users_str,
        );

        $this->post_body = json_encode($data);

        return $this->execute_curl(ParamKeys::QB_DIALOG_CREATE);
    }

    public function execute_curl($path, $value = '', $method = 'POST') {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, ParamKeys::QB_API_ENDPOINT . '/' . $path . $value);
        curl_setopt($curl, CURLOPT_POST, true); // Use POST
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->post_body); // Setup post body
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_POSTREDIR, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Receive server response

        if(!empty($this->qb_token)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'QuickBlox-REST-API-Version: 0.1.0',
                'QB-Token: ' . $this->qb_token
            ));
        }

        // Execute request and read response
        $response = curl_exec($curl);

        // Check errors
        if ($response) {
            $result = json_decode($response);
        } else {
            $result = curl_error($curl). '(' .curl_errno($curl). ')';
        }

        // Close connection
        curl_close($curl);

        return $result;
    }

    public function execute_curl_get($path, $value = '') {
        echo 'fffff';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, ParamKeys::QB_API_ENDPOINT . '/' . $path . $value);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Receive server response

        if(!empty($this->qb_token)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'QuickBlox-REST-API-Version: 0.1.0',
                'QB-Token: ' . $this->qb_token
            ));
        }

        // Execute request and read response
        $response = curl_exec($curl);

        // Check errors
        if ($response) {
            $result = json_decode($response);
        } else {
            $result = curl_error($curl). '(' .curl_errno($curl). ')';
        }

        // Close connection
        curl_close($curl);

        return $result;
    }

    private function checkTaskMuteStatus($taskId, $userId) {
        $taskNotificationSql = "SELECT * FROM cor_notification_status WHERE TASK_ID = :taskId AND USER_ID = :userId LIMIT 1";
        $taskNotificationStatement = $this->db->prepare($taskNotificationSql);
        $taskNotificationStatement->bindParam(":taskId", $taskId, \PDO::PARAM_STR);
        $taskNotificationStatement->bindParam(":userId", $userId, \PDO::PARAM_STR);
        $taskNotificationStatement->execute();
        $result = $taskNotificationStatement->fetch(\PDO::FETCH_ASSOC);

        if(!empty($result)) {
            if ($result['STATUS'] == 1) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public function logCron($title, $taskCount, $ids) {
        $ranAt = $this->convertTimestampToDate($this->getCurrentTimestamp());

        $this->db->beginTransaction();

        $statementCronLog = $this->db->prepare('INSERT INTO cor_cron_log (CRON_TITLE, RAN_AT, TASKS_UPDATED, TASK_IDS) VALUES(:cronTitle, :ranAt, :taskUpdated, :taskIds)');
        $statementCronLog->bindParam(":cronTitle", $title, \PDO::PARAM_STR);
        $statementCronLog->bindParam(":ranAt", $ranAt, \PDO::PARAM_STR);
        $statementCronLog->bindParam(":taskUpdated", $taskCount, \PDO::PARAM_STR);
        $statementCronLog->bindParam(":taskIds", implode(',',$ids), \PDO::PARAM_STR);
        $statementCronLog->execute();

        $this->db->commit();
    }
}