<?php
/**
 * Created by IntelliJ IDEA.
 * User: nayab
 * Date: 01-Nov-17
 * Time: 6:05 PM
 */

namespace Controllers;

use Constants\Messages;
use Constants\NotifyMessages;
use Constants\ParamKeys;
use Psr\Container\ContainerInterface;
use Validators\AuthValidatorController;
use Utils\PushNotifications as Notification;

class TaskController extends ControllerBase
{
    protected $container;
    protected $db;

    // constructor receives container instance
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->db = $this->container->primary;
    }
    /**
     * Description: Fetch Project tasks
     */
    public function fetchProjectTasks($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::PROJECT_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $memberSql = "SELECT PROJECT_ROLE FROM " . $this->getProjectMembers() . " WHERE USER_ID = :userId AND PROJECT_ID = :projectId";
                            $memberStatement = $this->db->prepare($memberSql);
                            $memberStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $memberStatement->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                            $memberStatement->execute();
                            $result = $memberStatement->fetchAll(\PDO::FETCH_ASSOC);

                            if($result[0]['PROJECT_ROLE'] == "MEMBER") {
                                $taskSql = "SELECT PROJECT_ID, TASK_ID, TASK_TITLE, TASK_DESCRIPTION, ASSIGNED_ID, 
                                               FULL_NAME, MOBILE_NUMBER as USER_NAME, PROFILE_FILENAME, CREATOR_ID, 
                                               CREATED_DATE, DUE_DATE, REPEAT_INTERVAL, FILE_PATH, TASK_STATUS, QB_DIALOG_ID 
                                        FROM ". $this->getProjectTasks() . " LEFT JOIN " . $this->getUserDetails() . " 
                                        ON " . $this->getUserDetails() . ".USER_ID = " . $this->getProjectTasks() . ".ASSIGNED_ID 
                                        WHERE PROJECT_ID = :projectId AND ASSIGNED_ID = :assignedId AND PARENT_TASK_ID IS NULL";
                            } else {
                                $taskSql = "SELECT PROJECT_ID, TASK_ID, TASK_TITLE, TASK_DESCRIPTION, ASSIGNED_ID, 
                                               FULL_NAME, MOBILE_NUMBER as USER_NAME, PROFILE_FILENAME, CREATOR_ID, 
                                               CREATED_DATE, DUE_DATE, REPEAT_INTERVAL, FILE_PATH, TASK_STATUS, QB_DIALOG_ID 
                                        FROM ". $this->getProjectTasks() . " LEFT JOIN " . $this->getUserDetails() . " 
                                        ON " . $this->getUserDetails() . ".USER_ID = " . $this->getProjectTasks() . ".ASSIGNED_ID 
                                        WHERE PROJECT_ID = :projectId AND PARENT_TASK_ID IS NULL";
                            }

                            $tasksStatement = $this->db->prepare($taskSql);
                            $tasksStatement->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);

                            if($result[0]['PROJECT_ROLE'] == "MEMBER") {
                                $tasksStatement->bindParam(":assignedId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            }
                            
                            $tasksStatement->execute();
                            $results = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

                            foreach ($results as $key => $data) {
                                $results[$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);

                                if (!empty($data['CC'])) {
                                    $tasks[$key]['CC'] = $this->getUsersFromCC($data['CC']);
                                }
                            }

                            if ($results) {
                                return $this->sendHttpResponseSuccess($response, $results);
                            } else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /**
     * Description: Create task for user //createTasksWeb
     */
    public function createTasks($request, $response, $arg) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::TASK_TITLE] == "" || $params[ParamKeys::CREATOR_ID] == "" || $params[ParamKeys::PROJECT_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $taskDuplicate = $this->db->prepare('select * from '. $this->getTasks() . ' where PROJECT_ID = :projectId AND TASK_TITLE = :taskTitle AND ASSIGNED_ID = :assignedId');
                            $taskDuplicate->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                            $taskDuplicate->bindParam(":taskTitle", $params[ParamKeys::TASK_TITLE], \PDO::PARAM_STR);
                            $taskDuplicate->bindParam(":assignedId", $params[ParamKeys::ASSIGNED_ID], \PDO::PARAM_STR);
                            $taskDuplicate->execute();
                            $taskDuplicate = $taskDuplicate->fetch(\PDO::FETCH_ASSOC);
                            if ($taskDuplicate) {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DUPLICATE_TASK_ASSIGNED_TO);
                            } else {
                                $this->db->beginTransaction();
                                $statementTask = $this->db->prepare('INSERT INTO ' . $this->getTasks() .
                                                                    ' (PROJECT_ID, PARENT_TASK_ID, TASK_TITLE, TASK_DESCRIPTION, CREATOR_ID, ASSIGNED_ID, CREATED_DATE, DUE_DATE, DUE_DATE_DT, CC) 
                                                                    VALUES(:projectId, :parentTaskId, :taskTitle, :taskDescription, :creatorId, :assignedId, :createdDate, :dueDate, :dueDateDt, :cc)');
                                $statementTask->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                                $statementTask->bindParam(":parentTaskId", $params[ParamKeys::PARENT_TASK_ID], \PDO::PARAM_STR);
                                $statementTask->bindParam(":taskTitle", $params[ParamKeys::TASK_TITLE], \PDO::PARAM_STR);
                                $statementTask->bindParam(":taskDescription", $params[ParamKeys::TASK_DESCRIPTION], \PDO::PARAM_STR);
                                $statementTask->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                $statementTask->bindParam(":assignedId", $params[ParamKeys::ASSIGNED_ID], \PDO::PARAM_STR);
                                $statementTask->bindParam(":createdDate", $params[ParamKeys::CREATED_DATE], \PDO::PARAM_STR);
                                $statementTask->bindParam(":dueDate", $params[ParamKeys::DUE_DATE], \PDO::PARAM_STR);
								
								$duedate = date("Y-m-d H:i:s",($params[ParamKeys::DUE_DATE]));
								
                                $statementTask->bindParam(":dueDateDt", $duedate, \PDO::PARAM_STR);
                                $statementTask->bindParam(":cc", $params[ParamKeys::CC], \PDO::PARAM_STR);
                                $statementTask->execute();
                                $taskid = $this->db->lastInsertId();
                                $this->db->commit();

                                if ($taskid > 0) {
                                    //$taskStatement = $this->db->prepare('select * from '. $this->getProjectTaskVW() . ' where PROJECT_ID = :projectId AND TASK_ID = :taskId');
                                    $sql = "SELECT `a`.`PROJECT_ID` AS `PROJECT_ID`, `a`.`TASK_ID` AS `TASK_ID`,  
                                                   `a`.`CREATOR_ID` AS `CREATOR_ID`, `a`.`TASK_TITLE` AS `TASK_TITLE`,  
                                                   `a`.`TASK_DESCRIPTION` AS `TASK_DESCRIPTION`, `a`.`ASSIGNED_ID` AS `ASSIGNED_ID`,  
                                                   `b`.`FULL_NAME` AS `FULL_NAME`, `b`.`USER_NAME` AS `USER_NAME`,  
                                                   `b`.`PROFILE_FILENAME` AS `PROFILE_FILENAME`, `a`.`CREATED_DATE` AS `CREATED_DATE`,  
                                                   `a`.`DUE_DATE` AS `DUE_DATE`, `a`.`REPEAT_INTERVAL` AS `REPEAT_INTERVAL`,  
                                                   `a`.`FILE_PATH` AS `FILE_PATH`, `a`.`TASK_STATUS` AS `TASK_STATUS`, `a`.`CC` 
                                            FROM `cor_project_tasks` `a` JOIN `ptf_user_details_vw` `b` 
                                            ON `a`.`ASSIGNED_ID` = `b`.`USER_ID` WHERE  `a`.`PROJECT_ID` = :projectId AND `a`.`TASK_ID` = :taskId";

                                    $taskStatement = $this->db->prepare($sql);
                                    $taskStatement->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                                    $taskStatement->bindParam(":taskId", $taskid, \PDO::PARAM_STR);
                                    $taskStatement->execute();
                                    $data = $taskStatement->fetch(\PDO::FETCH_ASSOC);

                                    $uploadedFiles = $request->getUploadedFiles();
                                    if (!empty($uploadedFiles)) {
                                        foreach ($uploadedFiles['TASK_IMAGES'] as $uploadedFile) {
                                            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                                                $filename = $this->moveUploadedFile('storage/tasks', $uploadedFile);

                                                $this->addTasksImages($filename, $taskid);
                                            }
                                        }
                                    }

                                    // send notification
                                    $device_tokens[] = $this->getDeviceToken($params[ParamKeys::ASSIGNED_ID]);
                                    $this->sendNotification($params[ParamKeys::CREATOR_ID], $params[ParamKeys::ASSIGNED_ID],
                                        $taskid, 'task', 'NEW_TASK_MEMBER_ADDED', $device_tokens, $params[ParamKeys::TASK_TITLE]);

                                    return $this->sendHttpResponseSuccess($response, $data);
                                } else {
                                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_CREATE);
                                }
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
        //this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /**
     * Description: Create task for user //createTasksWeb
     */
    public function createTasksWeb($request, $response, $arg) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::TASK_TITLE] == "" || $params[ParamKeys::CREATOR_ID] == "" || $params[ParamKeys::ASSIGNED_ID] == "" ) {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
							$ASSIGNED_ID = str_replace(' ', '', $params[ParamKeys::ASSIGNED_ID]);							
                            $statementUser = $this->db->prepare("select * from ". $this->getUserDetails() . " where MOBILE_NUMBER LIKE '%".$ASSIGNED_ID."%'");										
                            $statementUser->execute();
                            $userData = $statementUser->fetch(\PDO::FETCH_ASSOC);
							if($userData)
							{ 
								$ASSIGNED_ID = $userData['USER_ID'];
							}
							else
							{
							$ASSIGNED_ID = "";						
							}
                            $taskDuplicate = $this->db->prepare('select * from '. $this->getTasks() . ' where PROJECT_ID = NULL AND TASK_TITLE = :taskTitle AND ASSIGNED_ID = :assignedId');
                            $taskDuplicate->bindParam(":taskTitle", $params[ParamKeys::TASK_TITLE], \PDO::PARAM_STR);
                            $taskDuplicate->bindParam(":assignedId", $ASSIGNED_ID, \PDO::PARAM_INT);
                            $taskDuplicate->execute();							
                            $taskDuplicate = $taskDuplicate->fetch(\PDO::FETCH_ASSOC);
                            if ($taskDuplicate) {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DUPLICATE_TASK_ASSIGNED_TO);
                            } else {
                                $this->db->beginTransaction();
                                $statementTask = $this->db->prepare('INSERT INTO ' . $this->getTasks() .
                                                                    ' (PROJECT_ID, PARENT_TASK_ID, TASK_TITLE, TASK_DESCRIPTION, CREATOR_ID, ASSIGNED_ID, CREATED_DATE, DUE_DATE, DUE_DATE_DT, CC,REPEAT_INTERVAL) 
                                                                    VALUES(NULL, NULL, :taskTitle, :taskDescription, :creatorId, :assignedId , UNIX_TIMESTAMP(), :dueDate, :dueDateDt, :cc, :repInt)');
                                $statementTask->bindParam(":taskTitle", $params[ParamKeys::TASK_TITLE], \PDO::PARAM_STR);
                                $statementTask->bindParam(":taskDescription", $params[ParamKeys::TASK_DESCRIPTION], \PDO::PARAM_STR);								
                                $statementTask->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_INT);
                                $statementTask->bindParam(":assignedId", $ASSIGNED_ID, \PDO::PARAM_INT);								
                               
								
                                $statementTask->bindParam(":dueDate", $params[ParamKeys::DUE_DATE], \PDO::PARAM_STR);
								
								$duedate = "";
								if($params[ParamKeys::DUE_DATE]!="")
								{		
									$duedate = date("Y-m-d H:i:s",($params[ParamKeys::DUE_DATE]));						
                                	
								}
								$statementTask->bindParam(":dueDateDt", $duedate, \PDO::PARAM_STR);
								$CC = str_replace(' ', '', $params[ParamKeys::CC]);							
								
								$statementUserCC = $this->db->prepare("select * from ". $this->getUserDetailsVW() . " where MOBILE_NUMBER LIKE '%".$CC."%'");										
								$statementUserCC->execute();
								$userDataCC = $statementUserCC->fetch(\PDO::FETCH_ASSOC);
								if($userDataCC)
								$CC = $userDataCC['USER_ID'];
								else
								$CC = "";
                                $statementTask->bindParam(":cc", $CC);
								 $statementTask->bindParam(":repInt", $params[ParamKeys::REPEAT_INTERVAL], \PDO::PARAM_STR);
                                $statementTask->execute();
                                $taskid = $this->db->lastInsertId();
                                $this->db->commit();					
                                if ($taskid > 0) {
                                    //$taskStatement = $this->db->prepare('select * from '. $this->getProjectTaskVW() . ' where PROJECT_ID = :projectId AND TASK_ID = :taskId');
                                    $sql = "SELECT `a`.`PROJECT_ID` AS `PROJECT_ID`, `a`.`TASK_ID` AS `TASK_ID`,  
                                                   `a`.`CREATOR_ID` AS `CREATOR_ID`, `a`.`TASK_TITLE` AS `TASK_TITLE`,  
                                                   `a`.`TASK_DESCRIPTION` AS `TASK_DESCRIPTION`, `a`.`ASSIGNED_ID` AS `ASSIGNED_ID`,  
                                                   `b`.`FULL_NAME` AS `FULL_NAME`, `b`.`USER_NAME` AS `USER_NAME`,  
                                                   `b`.`PROFILE_FILENAME` AS `PROFILE_FILENAME`, `a`.`CREATED_DATE` AS `CREATED_DATE`,  
                                                   `a`.`DUE_DATE` AS `DUE_DATE`, `a`.`REPEAT_INTERVAL` AS `REPEAT_INTERVAL`,  
                                                   `a`.`FILE_PATH` AS `FILE_PATH`, `a`.`TASK_STATUS` AS `TASK_STATUS`, `a`.`CC` 
                                            FROM `cor_project_tasks` `a` JOIN `ptf_user_details_vw` `b` 
                                            ON `a`.`ASSIGNED_ID` = `b`.`USER_ID` WHERE  `a`.`PROJECT_ID` = :projectId AND `a`.`TASK_ID` = :taskId";

                                    $taskStatement = $this->db->prepare($sql);
                                    $taskStatement->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                                    $taskStatement->bindParam(":taskId", $taskid, \PDO::PARAM_STR);
                                    $taskStatement->execute();
                                    $data = $taskStatement->fetch(\PDO::FETCH_ASSOC);

                                    $uploadedFiles = $request->getUploadedFiles();
                                    if (!empty($uploadedFiles)) {
                                        foreach ($uploadedFiles['TASK_IMAGES'] as $uploadedFile) {
                                            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                                                $filename = $this->moveUploadedFile('storage/tasks', $uploadedFile);

                                                $this->addTasksImages($filename, $taskid);
                                            }
                                        }
                                    }

                                    // send notification
                                    $device_tokens[] = $this->getDeviceToken($params[ParamKeys::ASSIGNED_ID]);
                                    $this->sendNotification($params[ParamKeys::CREATOR_ID], $params[ParamKeys::ASSIGNED_ID],
                                        $taskid, 'task', 'NEW_TASK_MEMBER_ADDED', $device_tokens, $params[ParamKeys::TASK_TITLE]);

                                    return $this->sendHttpResponseSuccess($response, $data);
                                } else {
                                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_CREATE);
                                }
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
        //this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }


    /**
     * Description: Create personal task for user
     */
    public function createPersonalTask($request, $response, $arg) {
        try {
			$params = $request->getParsedBody();
			if(!$params)
			$params = $request->getParams(); 
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::TASK_TITLE] == "" || $params[ParamKeys::CREATOR_ID] == "" || $params[ParamKeys::ASSIGNED_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $taskDuplicate = $this->db->prepare('select * from '. $this->getTasks() . ' where PROJECT_ID is null AND TASK_TITLE = :taskTitle AND ASSIGNED_ID = :assignedId');
                            $taskDuplicate->bindParam(":taskTitle", $params[ParamKeys::TASK_TITLE], \PDO::PARAM_STR);
                            $taskDuplicate->bindParam(":assignedId", $params[ParamKeys::ASSIGNED_ID], \PDO::PARAM_STR);
                            $taskDuplicate->execute();
                            $taskDuplicate = $taskDuplicate->fetch(\PDO::FETCH_ASSOC);
                            if ($taskDuplicate) {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DUPLICATE_TASK_ASSIGNED_TO);
                            } else {
                                $this->db->beginTransaction();
                                $statementTask = $this->db->prepare('INSERT INTO ' . $this->getTasks() .
                                    ' (PARENT_TASK_ID, TASK_TITLE, TASK_DESCRIPTION, CREATOR_ID, ASSIGNED_ID, CREATED_DATE, DUE_DATE, DUE_DATE_DT, REPEAT_INTERVAL, CC) 
                                                                    VALUES(:parentTaskId, :taskTitle, :taskDescription, :creatorId, :assignedId, :createdDate, :dueDate, :dueDateDt, :repeatInterval, :cc)');
                                //$statementTask->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                                $statementTask->bindParam(":parentTaskId", $params[ParamKeys::PARENT_TASK_ID], \PDO::PARAM_STR);
                                $statementTask->bindParam(":taskTitle", $params[ParamKeys::TASK_TITLE], \PDO::PARAM_STR);
                                $statementTask->bindParam(":taskDescription", $params[ParamKeys::TASK_DESCRIPTION], \PDO::PARAM_STR);
                                $statementTask->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                $statementTask->bindParam(":assignedId", $params[ParamKeys::ASSIGNED_ID], \PDO::PARAM_STR);
								
								// rafiq changed for new php version
								$crdate = time();
								$duedatet = @date("Y-m-d H:i:s",($params[ParamKeys::DUE_DATE]));
                                $statementTask->bindParam(":createdDate", $crdate);
								
								
//								die('DueDate:'.$params[ParamKeys::DUE_DATE].'=dt:'.$duedatet);
                                $statementTask->bindParam(":dueDate", $params[ParamKeys::DUE_DATE], \PDO::PARAM_STR);
                                $statementTask->bindParam(":dueDateDt",$duedatet , \PDO::PARAM_STR);
                                $statementTask->bindParam(":repeatInterval", $params[ParamKeys::REPEAT_INTERVAL], \PDO::PARAM_STR);
                                $statementTask->bindParam(":cc", $params[ParamKeys::CC], \PDO::PARAM_STR);
                                $statementTask->execute();
                                $taskid = $this->db->lastInsertId();
                                $this->db->commit();
                                if ($taskid > 0) {
                                    //$taskStatement = $this->db->prepare('select * from '. $this->getProjectTasks() . ' where TASK_ID = :taskId');
                                    $sql = "SELECT `a`.`PROJECT_ID` AS `PROJECT_ID`, `a`.`TASK_ID` AS `TASK_ID`,  
                                                   `a`.`CREATOR_ID` AS `CREATOR_ID`, `a`.`TASK_TITLE` AS `TASK_TITLE`,  
                                                   `a`.`TASK_DESCRIPTION` AS `TASK_DESCRIPTION`, `a`.`ASSIGNED_ID` AS `ASSIGNED_ID`,  
                                                   `b`.`FULL_NAME` AS `FULL_NAME`, `b`.`USER_NAME` AS `USER_NAME`,  
                                                   `b`.`PROFILE_FILENAME` AS `PROFILE_FILENAME`, `a`.`CREATED_DATE` AS `CREATED_DATE`,  
                                                   `a`.`DUE_DATE` AS `DUE_DATE`, `a`.`REPEAT_INTERVAL` AS `REPEAT_INTERVAL`,  
                                                   `a`.`FILE_PATH` AS `FILE_PATH`, `a`.`TASK_STATUS` AS `TASK_STATUS`, `a`.`CC` 
                                            FROM `cor_project_tasks` `a` JOIN `ptf_user_details_vw` `b` 
                                            ON `a`.`ASSIGNED_ID` = `b`.`USER_ID` WHERE `a`.`TASK_ID` = :taskId";

                                    $taskStatement = $this->db->prepare($sql);
                                    //$taskStatement->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                                    $taskStatement->bindParam(":taskId", $taskid, \PDO::PARAM_STR);
                                    $taskStatement->execute();
                                    $data = $taskStatement->fetch(\PDO::FETCH_ASSOC);
                                    $uploadedFiles = $request->getUploadedFiles();
                                    if (!empty($uploadedFiles)) {
                                        foreach ($uploadedFiles['TASK_IMAGES'] as $uploadedFile) {
                                            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                                                $filename = $this->moveUploadedFile('storage/tasks', $uploadedFile);

                                                $this->addTasksImages($filename, $taskid);
                                            }
                                        }
                                    }

                                    // send notification
                                    $device_tokens[] = $this->getDeviceToken($params[ParamKeys::ASSIGNED_ID]);

                                    //if($params[ParamKeys::CREATOR_ID] != $params[ParamKeys::ASSIGNED_ID]) {
                                        $this->sendNotification($params[ParamKeys::CREATOR_ID], $params[ParamKeys::ASSIGNED_ID],
                                            $taskid, 'task', 'NEW_TASK_MEMBER_ADDED', $device_tokens, $params[ParamKeys::TASK_TITLE]);
                                    //}

                                    return $this->sendHttpResponseSuccess($response, Messages::MSG_SUCCESS_CREATE_TASK); //MSG_SUCCESS_CREATE_TASK
                                } else {
                                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_TASK_CREATE);
                                }
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            //$this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }


    /**
     * Description: Update tasks status
     */
    public function updateTaskStatus($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::TASK_ID] == "" || $params[ParamKeys::STATUS] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $this->db->beginTransaction();
                            $tasksStatement = $this->db->prepare('UPDATE '. $this->getTasks().' set TASK_STATUS = :status where TASK_ID = :taskId');
                            //$tasksStatement->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                            $tasksStatement->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_STR);
                            $tasksStatement->bindParam(":status", $params[ParamKeys::STATUS], \PDO::PARAM_STR);
                            $tasksStatement->execute();
                            if ($this->db->commit()) {
                                if(ParamKeys::STATUS != 'CLOSED') {
                                    if (ParamKeys::STATUS == 'OPEN')
                                        $statementUser = $this->db->prepare('select * from ' . $this->getTasks() . ' JOIN ' . $this->getUserDetails() . ' ON ' . $this->getUserDetails() . '.' . ParamKeys::USER_ID . ' = ' . $this->getTasks() . '.' . ParamKeys::ASSIGNED_ID . ' where TASK_ID = :taskId');
                                    else
                                        $statementUser = $this->db->prepare('select * from ' . $this->getTasks() . ' JOIN ' . $this->getUserDetails() . ' ON ' . $this->getUserDetails() . '.' . ParamKeys::USER_ID . ' = ' . $this->getTasks() . '.' . ParamKeys::CREATOR_ID . ' where TASK_ID = :taskId');

                                    $statementUser->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_STR);
                                    $statementUser->execute();
                                    $userData = $statementUser->fetch(\PDO::FETCH_ASSOC);
                                    if (ParamKeys::STATUS == 'OPEN') {
                                        $senderId = $userData['CREATOR_ID'];
                                        $receiverId = $userData['ASSIGNED_ID'];
                                    } else {
                                        $senderId = $userData['ASSIGNED_ID'];
                                        $receiverId = $userData['CREATOR_ID'];
                                    }
                                    $device_tokens[] = $this->getDeviceToken($userData['USER_ID']);
                                    $this->sendNotification($senderId, $receiverId, $params[ParamKeys::TASK_ID],'task','STATUS_CHANGED', $device_tokens, $params[ParamKeys::STATUS]);
                            
                                }

                                return $this->sendHttpResponseMessage($response, "Task Updated successfully");
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
//            $this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /**
     * Description: Update tasks
     */
    public function updateTask($request, $response) {
        try {
            //$params = $request->getParsedBody();
			$params = $request->getParams(); 
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::TASK_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $this->db->beginTransaction();
                            $tasksStatement = $this->db->prepare('UPDATE '. $this->getTasks().' set ASSIGNED_ID = :assignedId, TASK_TITLE = :taskTitle, TASK_DESCRIPTION = :taskDesc, DUE_DATE = :dueDate, REPEAT_INTERVAL = :repeatInterval, CC = :cc  where TASK_ID = :taskId');
                            $tasksStatement->bindParam(":taskTitle", $params[ParamKeys::TASK_TITLE], \PDO::PARAM_STR);
                            $tasksStatement->bindParam(":taskDesc", $params[ParamKeys::TASK_DESCRIPTION], \PDO::PARAM_STR);
                            $tasksStatement->bindParam(":dueDate", $params[ParamKeys::DUE_DATE], \PDO::PARAM_STR);
                            $tasksStatement->bindParam(":repeatInterval", $params[ParamKeys::REPEAT_INTERVAL], \PDO::PARAM_STR);
                            //$tasksStatement->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                            $tasksStatement->bindParam(":assignedId", $params[ParamKeys::ASSIGNED_ID], \PDO::PARAM_STR);
                            $tasksStatement->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_STR);
                            $tasksStatement->bindParam(":cc", $params[ParamKeys::CC], \PDO::PARAM_STR);
                            $tasksStatement->execute();

                            if ($this->db->commit()) {

                                $taskid = $params[ParamKeys::TASK_ID];

                                $uploadedFiles = $request->getUploadedFiles();
                                if (!empty($uploadedFiles)) {

                                   // $this->deleteTaskImages($taskid);

                                    foreach ($uploadedFiles['TASK_IMAGES'] as $uploadedFile) {
                                        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                                            $filename = $this->moveUploadedFile('storage/tasks', $uploadedFile);

                                            $this->addTasksImages($filename, $taskid);
                                        }
                                    }
                                } else {
                                   // $this->deleteTaskImages($taskid);
                                }

                                return $this->sendHttpResponseMessage($response, "Task Updated Successfully of TASK_ID: " . $params[ParamKeys::TASK_ID]);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            //$this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /**
     * Description: Assign tasks
     */
    public function assignTask($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::TASK_ID] == "" || $params[ParamKeys::USER_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
                        $this->db->beginTransaction();
                        $tasksStatement = $this->db->prepare('UPDATE '. $this->getTasks().' set ASSIGNED_ID = :userId, TASK_DESCRIPTION = :taskDescription WHERE TASK_ID = :taskId');
                        $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_INT);
                        $tasksStatement->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_INT);
                        $tasksStatement->bindParam(":taskDescription", $params[ParamKeys::TASK_DESCRIPTION], \PDO::PARAM_STR);
                        $tasksStatement->execute();

                        if ($this->db->commit()) {
                            return $this->sendHttpResponseMessage($response, "Task has been assigned to user");
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            //$this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /**
     * Description: fetch all user's projects and its tasks and sub tasks
     */
    public function fetchUserTasks($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $taskSql = "select * from ". $this->getProjects() . " where CREATOR_ID = :userId";
                            $tasksStatement = $this->db->prepare($taskSql);
                            $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $tasksStatement->execute();
                            $projects = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
                            $resp['DATA']['PROJECTS'] = array();
                            $resp['DATA']['PERSONAL_TASKS'] = $this->_get_personal_tasks($params[ParamKeys::USER_ID]);;
                            if ($projects) {
                                // get project tasks
                                foreach($projects as $key => $value){
                                    $projects[$key]['TASKS'] = $this->_get_project_tasks($value['PROJECT_ID']);
                                }
                                $resp['DATA']['PROJECTS'] = $projects;
                                //$resp['DATA']['PERSONAL_TASKS'] = $this->_get_personal_tasks($params[ParamKeys::USER_ID]);
                                //return $this->sendHttpResponseSuccess($response, $resp);
                            } else {
                                //return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                            }

                            foreach($resp['DATA']['PERSONAL_TASKS'] as $key => $data) {
                                $resp['DATA']['PERSONAL_TASKS'][$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);
                            }

                            return $this->sendHttpResponseSuccess($response, $resp);
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	
	
    /**
     * Description: fetch all user's tasks of due today 24 hours and all types
     */
    public function fetchDueTasks($request, $response) {
        try {
           // $params = $request->getQueryParams();
		   $params = $request->getParams(); 
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            if (isset($params[ParamKeys::STATUS]) && $params[ParamKeys::STATUS] != '') {
                                $taskSql = "select * from ". $this->getTasks() . " where CREATOR_ID = :userId AND TASK_STATUS = :status";
                            } elseif (isset($params[ParamKeys::CURRENT_DATE])) {
                                if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "ME") {
                                    $taskSql = "select task.*, ud.FULL_NAME from cor_project_tasks task, ptf_user_details ud where task.CREATOR_ID = ud.USER_ID AND task.CREATOR_ID != :userId AND task.ASSIGNED_ID = :userId AND task.DUE_DATE <= :dueDate AND task.TASK_STATUS != 'COMPLETED' AND task.DUE_DATE != 0 AND task.PROJECT_ID IS NULL";
									
									//die($taskSql);
                                } else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "PERSONAL") {
                                    $taskSql = "select * from " . $this->getTasks() . " where CREATOR_ID = :userId AND ASSIGNED_ID = :userId AND DUE_DATE <= :dueDate AND TASK_STATUS != 'COMPLETED' AND DUE_DATE != 0 AND PROJECT_ID IS NULL";
                                } else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "ASSIGNEDOTHER") {
                                    $taskSql = "select task.*, ud.FULL_NAME from cor_project_tasks task, ptf_user_details ud where task.ASSIGNED_ID = ud.USER_ID AND task.CREATOR_ID = :userId AND task.ASSIGNED_ID != :userId AND task.DUE_DATE <= :dueDate AND task.TASK_STATUS != 'COMPLETED' AND task.DUE_DATE != 0 AND cor_project_tasks.PROJECT_ID IS NULL";
									//die($taskSql);
                                }else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "PERSONALDUE") {
                                    $taskSql = "SELECT cor_project_tasks.*, IF(cor_notification_status.STATUS IS NULL, 1, cor_notification_status.STATUS) as STATUS  FROM cor_project_tasks left join cor_notification_status on (cor_project_tasks.TASK_ID = cor_notification_status.TASK_ID ) where cor_project_tasks.CREATOR_ID = :userId AND cor_project_tasks.ASSIGNED_ID = :userId AND cor_project_tasks.DUE_DATE <= :dueDate AND cor_project_tasks.TASK_STATUS != 'COMPLETED' AND cor_project_tasks.DUE_DATE != 0 AND cor_project_tasks.REPEAT_INTERVAL = '' AND cor_project_tasks.PROJECT_ID IS NULL";
									
									
									
                                }
								else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "PERSONALCTR") {
                                    $taskSql = "SELECT COUNT(*) as total, COUNT(IF(REPEAT_INTERVAL='',1,null)) as nonrepeated, COUNT(IF(REPEAT_INTERVAL !='',1,null)) as repeated FROM cor_project_tasks where CREATOR_ID = :userId AND ASSIGNED_ID = :userId AND DUE_DATE <= :dueDate AND TASK_STATUS != 'COMPLETED' AND DUE_DATE != 0 AND cor_project_tasks.PROJECT_ID IS NULL";
                                }
								else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "MEDUE") {
                                    $taskSql = "select * from " . $this->getTasks() . " where CREATOR_ID = :userId AND ASSIGNED_ID = :userId AND DUE_DATE <= :dueDate AND TASK_STATUS != 'COMPLETED' AND DUE_DATE != 0 AND REPEAT_INTERVAL = '' AND cor_project_tasks.PROJECT_ID IS NULL";
                                }else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "PERSONALREP") {
                                    $taskSql = "SELECT cor_project_tasks.*, IF(cor_notification_status.STATUS IS NULL, 1, cor_notification_status.STATUS) as STATUS  FROM cor_project_tasks left join cor_notification_status on (cor_project_tasks.TASK_ID = cor_notification_status.TASK_ID ) where CREATOR_ID = :userId AND ASSIGNED_ID = :userId AND DUE_DATE <= :dueDate AND TASK_STATUS != 'COMPLETED' AND TASK_STATUS != 'CLOSED' AND DUE_DATE != 0 AND REPEAT_INTERVAL != '' AND cor_project_tasks.PROJECT_ID IS NULL";
                                }
								else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "MECTR") {
                                    $taskSql = "SELECT COUNT(*) as total, COUNT(IF(REPEAT_INTERVAL='',1,null)) as nonrepeated, COUNT(IF(REPEAT_INTERVAL !='',1,null)) as repeated FROM cor_project_tasks task where task.CREATOR_ID = :assignMeID AND task.CREATOR_ID != :userId AND task.ASSIGNED_ID = :userId AND task.DUE_DATE <= :dueDate AND task.TASK_STATUS != 'COMPLETED' AND task.DUE_DATE != 0 AND task.PROJECT_ID IS NULL";
									 }
								else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "OTHERCTR") {
                                    $taskSql = "SELECT COUNT(*) as total, COUNT(IF(REPEAT_INTERVAL='',1,null)) as nonrepeated, COUNT(IF(REPEAT_INTERVAL !='',1,null)) as repeated FROM cor_project_tasks task where task.CREATOR_ID = :userId AND task.ASSIGNED_ID != :userId AND task.ASSIGNED_ID = :assignMeID AND task.DUE_DATE <= :dueDate AND task.TASK_STATUS != 'COMPLETED' AND task.DUE_DATE != 0 AND task.PROJECT_ID IS NULL";
									 
									 }
								 else {
                                    $taskSql = "select * from " . $this->getTasks() . " where ASSIGNED_ID = :userId AND DUE_DATE <= :dueDate AND TASK_STATUS != 'COMPLETED' AND DUE_DATE != 0 AND cor_project_tasks.PROJECT_ID IS NULL";
									//die($taskSql);
                                }
                            } elseif (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === 'ASSIGNED') {
                                $taskSql = "select * from ". $this->getTasks() . " where CREATOR_ID != :userId AND ASSIGNED_ID = :userId AND cor_project_tasks.PROJECT_ID IS NULL ORDER BY TASK_ID DESC";
                            }
							else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "METASKCTR") {
                                    $taskSql = "SELECT COUNT(*) as total, COUNT(IF((REPEAT_INTERVAL=''AND DUE_DATE=0),1,NULL)) as todo, COUNT(IF((REPEAT_INTERVAL=''AND DUE_DATE!=0),1,NULL)) as nonrepeated, COUNT(IF((REPEAT_INTERVAL!=''),1,NULL)) as repeated
 FROM cor_project_tasks task where task.CREATOR_ID = :assignMeID AND task.CREATOR_ID != :userId AND task.ASSIGNED_ID = :userId AND task.PROJECT_ID IS NULL";
									 }
									 else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "OTHERTASKCTR") {
                                    $taskSql = "SELECT COUNT(*) as total, COUNT(IF((REPEAT_INTERVAL=''AND DUE_DATE=0),1,NULL)) as todo, COUNT(IF((REPEAT_INTERVAL=''AND DUE_DATE!=0),1,NULL)) as nonrepeated, COUNT(IF((REPEAT_INTERVAL!=''),1,NULL)) as repeated
 FROM cor_project_tasks task where task.CREATOR_ID = :userId AND task.ASSIGNED_ID != :userId AND task.ASSIGNED_ID = :assignMeID AND task.PROJECT_ID IS NULL";
									 }
									 else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "MYPERSONALCTR") {
                                    $taskSql = "SELECT COUNT(*) as total, COUNT(IF((REPEAT_INTERVAL=''AND DUE_DATE=0),1,NULL)) as todo, COUNT(IF((REPEAT_INTERVAL=''AND DUE_DATE!=0),1,NULL)) as nonrepeated, COUNT(IF((REPEAT_INTERVAL!=''),1,NULL)) as repeated
 FROM cor_project_tasks task where task.CREATOR_ID = :userId AND task.ASSIGNED_ID = :userId AND task.PROJECT_ID IS NULL";
 //die($taskSql );
                                }
								else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "MYPERSONALREP") {
                                    $taskSql = "SELECT cor_project_tasks.*, IF(cor_notification_status.STATUS IS NULL, 1, cor_notification_status.STATUS) as STATUS  FROM cor_project_tasks left join cor_notification_status on (cor_project_tasks.TASK_ID = cor_notification_status.TASK_ID ) where cor_project_tasks.CREATOR_ID = :userId AND cor_project_tasks.ASSIGNED_ID = :userId AND cor_project_tasks.REPEAT_INTERVAL != '' AND cor_project_tasks.PROJECT_ID IS NULL";
                                }
								else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "PERSONALNOREP") {
                                    $taskSql = "SELECT cor_project_tasks.*, IF(cor_notification_status.STATUS IS NULL, 1, cor_notification_status.STATUS) as STATUS  FROM cor_project_tasks left join cor_notification_status on (cor_project_tasks.TASK_ID = cor_notification_status.TASK_ID ) where cor_project_tasks.CREATOR_ID = :userId AND cor_project_tasks.ASSIGNED_ID = :userId AND cor_project_tasks.DUE_DATE != 0 AND cor_project_tasks.REPEAT_INTERVAL = '' AND cor_project_tasks.PROJECT_ID IS NULL";
                                }
								else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "PERSONALTODO") {
                                    $taskSql = "SELECT cor_project_tasks.*, IF(cor_notification_status.STATUS IS NULL, 1, cor_notification_status.STATUS) as STATUS  FROM cor_project_tasks left join cor_notification_status on (cor_project_tasks.TASK_ID = cor_notification_status.TASK_ID ) where cor_project_tasks.CREATOR_ID = :userId AND cor_project_tasks.ASSIGNED_ID = :userId AND cor_project_tasks.DUE_DATE = 0  AND cor_project_tasks.REPEAT_INTERVAL = '' AND cor_project_tasks.PROJECT_ID IS NULL  ORDER BY cor_project_tasks.DUE_DATE ASC";
                                }
							 else {
                                $taskSql = "select * from ". $this->getTasks() . " where CREATOR_ID = :userId AND cor_project_tasks.PROJECT_ID IS NULL";
                            }
							
//die($taskSql);
                            $tasksStatement = $this->db->prepare($taskSql);

                            if (isset($params[ParamKeys::ASSIGNED_ID]) && $params[ParamKeys::ASSIGNED_ID] !== "") {
                                $tasksStatement->bindParam(":assignMeID", $params[ParamKeys::ASSIGNED_ID], \PDO::PARAM_STR);
                            }
							
							 if (isset($params[ParamKeys::STATUS]) && $params[ParamKeys::STATUS] !== "") {
                                $tasksStatement->bindParam(":status", $params[ParamKeys::STATUS], \PDO::PARAM_STR);
                            }

                            if (isset($params[ParamKeys::CURRENT_DATE]) && $params[ParamKeys::CURRENT_DATE] !== "") {								
								
								$datenew = strtotime("+2 day", strtotime($params[ParamKeys::CURRENT_DATE]));
                                $tasksStatement->bindParam(":dueDate", $datenew, \PDO::PARAM_STR);
								
                            }
//die(date("Y-m-d H:i:s",$datenew));

//die($datenew);
                            if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "PERSONAL") {
                                $tasksStatement->bindParam(":assignedId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            }

                            $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $tasksStatement->execute();
                            $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

                            foreach ($tasks as $key => $data) {
								 if (!empty($data['TASK_ID'])) {
                                $tasks[$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);
								 }

                                if (!empty($data['CC'])) {
                                    $tasks[$key]['CC'] = $this->getUsersFromCC($data['CC']);
                                }
                            }

                            if ($tasks) {
                                return $this->sendHttpResponseSuccess($response, $tasks);
                            } else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /**
     * Description: fetch all user's projects and its tasks and sub tasks
     */
    public function fetchTasks($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            if (isset($params[ParamKeys::STATUS]) && $params[ParamKeys::STATUS] != '') {
                                $taskSql = "select * from ". $this->getTasks() . " where CREATOR_ID = :userId AND TASK_STATUS = :status AND PROJECT_ID IS NULL";
                            } elseif (isset($params[ParamKeys::CURRENT_DATE])) {
                                if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "ME") {
                                    $taskSql = "select * from " . $this->getTasks() . " where CREATOR_ID != :userId AND ASSIGNED_ID = :userId AND DUE_DATE <= :dueDate AND TASK_STATUS != 'COMPLETED' AND PROJECT_ID IS NULL";
                                } else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "PERSONAL") {
                                    $taskSql = "select * from " . $this->getTasks() . " where CREATOR_ID = :userId AND ASSIGNED_ID = :userId AND DUE_DATE <= :dueDate AND TASK_STATUS != 'COMPLETED' AND PROJECT_ID IS NULL";
                                } else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "ASSIGNED") {
                                    $taskSql = "select * from " . $this->getTasks() . " where CREATOR_ID = :userId AND ASSIGNED_ID != :userId AND DUE_DATE <= :dueDate AND TASK_STATUS != 'COMPLETED' AND PROJECT_ID IS NULL";
                                } else {
                                    $taskSql = "select * from " . $this->getTasks() . " where ASSIGNED_ID = :userId AND DUE_DATE <= :dueDate AND TASK_STATUS != 'COMPLETED' AND PROJECT_ID IS NULL"; 
                                }
                            } elseif (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === 'ASSIGNED') {
                                $taskSql = "select * from ". $this->getTasks() . " where CREATOR_ID != :userId AND ASSIGNED_ID = :userId AND PROJECT_ID IS NULL ORDER BY TASK_ID DESC";
                            } else {
                                $taskSql = "select * from ". $this->getTasks() . " where CREATOR_ID = :userId AND PROJECT_ID IS NULL";
                            }


                            $tasksStatement = $this->db->prepare($taskSql);

                            if (isset($params[ParamKeys::STATUS]) && $params[ParamKeys::STATUS] !== "") {
                                $tasksStatement->bindParam(":status", $params[ParamKeys::STATUS], \PDO::PARAM_STR);
                            }

                            if (isset($params[ParamKeys::CURRENT_DATE]) && $params[ParamKeys::CURRENT_DATE] !== "") {								
								
								$datenew = strtotime("+2 day", strtotime($params[ParamKeys::CURRENT_DATE]));
                                $tasksStatement->bindParam(":dueDate", $datenew, \PDO::PARAM_STR);
                            }
//die(date("Y-m-d H:i:s",$datenew));
                            if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "PERSONAL") {
                                $tasksStatement->bindParam(":assignedId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            }

                            $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $tasksStatement->execute();
                            $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

                            foreach ($tasks as $key => $data) {
                                $tasks[$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);

                                if (!empty($data['CC'])) {
                                    $tasks[$key]['CC'] = $this->getUsersFromCC($data['CC']);
                                }
                            }

                            if ($tasks) {
                                return $this->sendHttpResponseSuccess($response, $tasks);
                            } else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

   /**
     * Description: fetch all tasks assigned to others users
     */
    public function fetchOthersTasks($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {                           
                                 if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "ASSIGNEDOTHERS") {
                                    $taskSql = "select * from " . $this->getTasks() . " where CREATOR_ID = :userId AND ASSIGNED_ID != :userId AND ASSIGNED_ID != 0 AND PROJECT_ID IS NULL ORDER BY DUE_DATE ASC ";
                                }  
								else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "USERSLIST") {
                                    $taskSql = "SELECT DISTINCT(cor_project_tasks.ASSIGNED_ID) AS ASSIGNED_ID, ptf_user_details.*  FROM cor_project_tasks, ptf_user_details WHERE ptf_user_details.USER_ID = cor_project_tasks.ASSIGNED_ID AND cor_project_tasks.CREATOR_ID = :userId AND cor_project_tasks.ASSIGNED_ID != :userId AND cor_project_tasks.ASSIGNED_ID != 0 AND PROJECT_ID IS NULL ORDER BY cor_project_tasks.DUE_DATE ASC";
                                }
								else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "USERASSIGNEDOTHERS") {
                                     $taskSql = "select * from " . $this->getTasks() . " where CREATOR_ID = :userId AND ASSIGNED_ID = :assignedID AND PROJECT_ID IS NULL ORDER BY DUE_DATE ASC ";
                                }
                    
							 $tasksStatement = $this->db->prepare($taskSql);
                           $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
						   $tasksStatement->bindParam(":assignedID", $params[ParamKeys::ASSIGNED_ID], \PDO::PARAM_STR);
                            $tasksStatement->execute();
                            $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);                          

                           return $this->sendHttpResponseSuccess($response, $tasks);
						   
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
				
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	
	 public function fetchOthersTasksDD($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "" || !isset($params[ParamKeys::CURRENT_DATE]) ) {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {					
				                     
                                if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "USERSLIST") {
                                    $taskSql = "SELECT DISTINCT(cor_project_tasks.ASSIGNED_ID) AS ASSIGNED_ID, ptf_user_details.*  FROM cor_project_tasks, ptf_user_details WHERE ptf_user_details.USER_ID = cor_project_tasks.ASSIGNED_ID AND cor_project_tasks.CREATOR_ID = :userId AND cor_project_tasks.ASSIGNED_ID != :userId AND cor_project_tasks.ASSIGNED_ID != 0 AND cor_project_tasks.DUE_DATE <= :dueDate AND cor_project_tasks.TASK_STATUS != 'COMPLETED' AND cor_project_tasks.DUE_DATE != 0 AND cor_project_tasks.PROJECT_ID IS NULL ORDER BY cor_project_tasks.DUE_DATE ASC";
                                }
								else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "USERASSIGNEDOTHERS") {
                                     $taskSql = "SELECT cor_project_tasks.* FROM cor_project_tasks where cor_project_tasks.CREATOR_ID = :userId AND cor_project_tasks.ASSIGNED_ID != :userId AND cor_project_tasks.ASSIGNED_ID = :assignedID AND  cor_project_tasks.TASK_STATUS != 'COMPLETED' AND cor_project_tasks.DUE_DATE != 0 AND cor_project_tasks.DUE_DATE <= :dueDate AND cor_project_tasks.PROJECT_ID IS NULL ORDER BY cor_project_tasks.DUE_DATE ASC";
                                }
                    
							 $tasksStatement = $this->db->prepare($taskSql); 
                           $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
						   
						     if (isset($params[ParamKeys::CURRENT_DATE]) && $params[ParamKeys::CURRENT_DATE] !== "") {								
								
								$datenew = strtotime("+2 day", strtotime($params[ParamKeys::CURRENT_DATE]));
                                $tasksStatement->bindParam(":dueDate", $datenew, \PDO::PARAM_STR);
                            }
							 if (isset($params[ParamKeys::ASSIGNED_ID]) && $params[ParamKeys::ASSIGNED_ID] !== "") {								
                                $tasksStatement->bindParam(":assignedID", $params[ParamKeys::ASSIGNED_ID], \PDO::PARAM_STR);
                            }
                            $tasksStatement->execute();
                            $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);                          

                           return $this->sendHttpResponseSuccess($response, $tasks);
						   
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
				
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

 /**
     * Description: fetch all tasks assigned to me
     */
     public function fetchMeTasks($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {                           
                                 if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "ASSIGNEDME") {
                                    $taskSql = "select * from " . $this->getTasks() . " where CREATOR_ID != :userId AND ASSIGNED_ID = :userId AND ASSIGNED_ID != 0 AND PROJECT_ID IS NULL ORDER BY DUE_DATE ASC ";
                                }  
								else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "USERSLIST") {
									 if (isset($params[ParamKeys::CURRENT_DATE])) {
										  $taskSql = "SELECT DISTINCT(cor_project_tasks.CREATOR_ID) AS CREATOR_ID, ptf_user_details.*  FROM cor_project_tasks, ptf_user_details WHERE ptf_user_details.USER_ID = cor_project_tasks.CREATOR_ID AND cor_project_tasks.CREATOR_ID != :userId AND cor_project_tasks.ASSIGNED_ID = :userId AND cor_project_tasks.CREATOR_ID != 0 AND cor_project_tasks.DUE_DATE <= :dueDate AND cor_project_tasks.TASK_STATUS != 'COMPLETED' AND cor_project_tasks.DUE_DATE != 0 AND cor_project_tasks.PROJECT_ID IS NULL ORDER BY cor_project_tasks.DUE_DATE ASC"; 
										 } else {
									
                                    $taskSql = "SELECT DISTINCT(cor_project_tasks.CREATOR_ID) AS CREATOR_ID, ptf_user_details.*  FROM cor_project_tasks, ptf_user_details WHERE ptf_user_details.USER_ID = cor_project_tasks.CREATOR_ID AND cor_project_tasks.CREATOR_ID != :userId AND cor_project_tasks.ASSIGNED_ID = :userId AND cor_project_tasks.CREATOR_ID != 0 AND cor_project_tasks.PROJECT_ID IS NULL ORDER BY cor_project_tasks.DUE_DATE ASC"; }
                                }
								else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "USERASSIGNEDME") {
									 if (isset($params[ParamKeys::CURRENT_DATE])) {
									
                                     $taskSql = "SELECT cor_project_tasks.*, IF(cor_notification_status.STATUS IS NULL, 1, cor_notification_status.STATUS) as STATUS  FROM cor_project_tasks left join cor_notification_status on (cor_project_tasks.TASK_ID = cor_notification_status.TASK_ID ) where cor_project_tasks.CREATOR_ID = :creatorID AND cor_project_tasks.CREATOR_ID != :userId AND cor_project_tasks.ASSIGNED_ID = :userId AND  cor_project_tasks.TASK_STATUS != 'COMPLETED' AND cor_project_tasks.DUE_DATE != 0 AND cor_project_tasks.PROJECT_ID IS NULL ORDER BY cor_project_tasks.DUE_DATE ASC";
									 }
									 else {
                                    $taskSql = "SELECT cor_project_tasks.*, IF(cor_notification_status.STATUS IS NULL, 1, cor_notification_status.STATUS) as STATUS  FROM cor_project_tasks left join cor_notification_status on (cor_project_tasks.TASK_ID = cor_notification_status.TASK_ID ) where cor_project_tasks.CREATOR_ID = :creatorID AND cor_project_tasks.CREATOR_ID != :userId AND cor_project_tasks.ASSIGNED_ID = :userId AND cor_project_tasks.PROJECT_ID IS NULL  ORDER BY cor_project_tasks.DUE_DATE ASC"; }
                             // die($taskSql);
							 //SELECT COUNT(*) as total, COUNT(IF(REPEAT_INTERVAL='',1,null)) as nonrepeated, COUNT(IF(REPEAT_INTERVAL !='',1,null)) as repeated FROM cor_project_tasks task where task.CREATOR_ID = :creatorID AND task.CREATOR_ID != :userId AND task.ASSIGNED_ID = :userId AND task.DUE_DATE <= :dueDate AND task.TASK_STATUS != 'COMPLETED' AND task.DUE_DATE != 0 ORDER BY DUE_DATE ASC
							   }
                   					
					
							 $tasksStatement = $this->db->prepare($taskSql);
                           $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
						   
						   
						    if (isset($params[ParamKeys::CURRENT_DATE]) && $params[ParamKeys::CURRENT_DATE] !== "") {								
								
								$datenew = strtotime("+2 day", strtotime($params[ParamKeys::CURRENT_DATE]));
                                $tasksStatement->bindParam(":dueDate", $datenew, \PDO::PARAM_STR);
                            }
							if (isset($params[ParamKeys::CREATOR_ID]) && $params[ParamKeys::CREATOR_ID] !== "") {
							$tasksStatement->bindParam(":creatorID", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
							}
                            $tasksStatement->execute();
                            $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);        
							
                            if ($tasks) {
                                return $this->sendHttpResponseSuccess($response, $tasks);
                            } else {
                                return $this->sendHttpResponseSuccess($response, $tasks);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
				
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }


    /**
     * Description: fetch tasks details.
     */
    public function fetchDetails($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::OBJECT_ID] == "" || $params[ParamKeys::OBJECT_TYPE] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            if ($params[ParamKeys::OBJECT_TYPE] == "task") {

                                //$taskSql = "select * from ". $this->getProjectTaskVW() . " where TASK_ID = :objectId";
                                $taskSql = "select PROJECT_ID, TASK_ID, TASK_TITLE, TASK_DESCRIPTION, ASSIGNED_ID, CC, 
                                                    FULL_NAME, MOBILE_NUMBER as USER_NAME, PROFILE_FILENAME, CREATOR_ID,
                                                     CREATED_DATE, DUE_DATE, REPEAT_INTERVAL, FILE_PATH, TASK_STATUS
                                from ". $this->getProjectTasks() . " LEFT JOIN " . $this->getUserDetails() . " ON " . $this->getUserDetails() . ".USER_ID = " . $this->getProjectTasks() . ".ASSIGNED_ID WHERE TASK_ID = :objectId";
								
								
								
                            } elseif($params[ParamKeys::OBJECT_TYPE] == "project") {

                                $taskSql = "select * from ". $this->getProjects() . " where PROJECT_ID = :objectId";
                                $taskSqlMembers = "select * from ". $this->getProjectsMembersVW() . " where PROJECT_ID = :objectId";
                                $tasksMembers = $this->db->prepare($taskSqlMembers);
                                $tasksMembers->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                                $tasksMembers->execute();
                            } elseif($params[ParamKeys::OBJECT_TYPE] == "chats") {

                                $taskSql = "select cor_project_tasks.PROJECT_ID, cor_project_tasks.TASK_ID, TASK_TITLE, TASK_DESCRIPTION, ASSIGNED_ID, FULL_NAME, 
                                                   MOBILE_NUMBER as USER_NAME, PROFILE_FILENAME, CREATOR_ID, CREATED_DATE, DUE_DATE, REPEAT_INTERVAL, 
                                                   FILE_PATH, TASK_STATUS 
                                            from cor_notifications left join cor_chats on cor_chats.CHAT_ID = cor_notifications.OBJECT_ID 
                                            left join cor_project_tasks ON cor_project_tasks.TASK_ID = cor_chats.TASK_ID 
                                            left join ptf_user_details ON ptf_user_details.USER_ID = cor_project_tasks.CREATOR_ID where OBJECT_ID = :objectId";
                            }

                            $tasksStatement = $this->db->prepare($taskSql);
                            $tasksStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                            $tasksStatement->execute();
                            $details = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

// for status of notification
//SELECT STATUS FROM cor_notification_status WHERE USER_ID = 237 AND TASK_ID = 2242
							$taskSql2 = "SELECT STATUS FROM cor_notification_status WHERE TASK_ID = :objectId";
						  	$tasksStatement2 = $this->db->prepare($taskSql2);
                            $tasksStatement2->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                            $tasksStatement2->execute();
                            $record = $tasksStatement2->fetchAll(\PDO::FETCH_ASSOC);								
								
								
								
								
								

                            if($params[ParamKeys::OBJECT_TYPE] == "project")
                                $details[0]['MEMBERS'] = $tasksMembers->fetchAll(\PDO::FETCH_ASSOC);

                            $details[0]['IMAGES'] = $this->getTaskImages($details[0]['TASK_ID']);
							
							$details[0]['IMAGES'] = $this->getTaskImages($details[0]['TASK_ID']);
							if(isset($record[0]['STATUS'])){
							$details[0]['TASK_NOTIFICATION_STATUS'] = $record[0]['STATUS'];
							}
							else
							$details[0]['TASK_NOTIFICATION_STATUS'] =1;
							
							
							//$details[0]['TASK_NOTIFICATION_STATUS'] = $record[0]['STATUS'];

                            if ($details) {
                                return $this->sendHttpResponseSuccess($response, $details[0]);
                            } else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                            }

                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	
	 public function fetchMemberDetails($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
							 $taskSql = "SELECT * FROM ptf_user_details WHERE USER_ID = :userId";
							 $tasksStatement = $this->db->prepare($taskSql);
							 $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID]);
       					 	$tasksStatement->execute();
       					 	$result = $tasksStatement->fetch(\PDO::FETCH_ASSOC);
							return $this->sendHttpResponseSuccess($response, $result);
							
							
							} else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /**
     * Description: fetch tasks Statues.
     */
    public function fetchStatusTasks($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
//                    if ($params[ParamKeys::PROJECT_ID] == "") {
//                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
//                    }
                    //else {
                        if ($this->db) {
                            $statusList = explode(',', $params[ParamKeys::STATUS]);
                            foreach($statusList as $key => $value) {
                                $statusArray[$key] = $this->db->quote($value);
                            }
                            $statusArray = join(', ', $statusArray);
                            if ($params[ParamKeys::STATUS] != "" && $params[ParamKeys::PROJECT_ID] != "") {
                                $taskSql = "select * from ". $this->getTasks() . " where PROJECT_ID = :projectId AND TASK_STATUS IN($statusArray)";
                            }else {
                                $taskSql = "select * from ". $this->getTasks() . " where TASK_STATUS IN($statusArray) ";

                            }
                            $tasksStatement = $this->db->prepare($taskSql);
                            if ($params[ParamKeys::STATUS] !== "" && $params[ParamKeys::PROJECT_ID] !== "") {
                                $tasksStatement->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                            }
                            $tasksStatement->execute();

                            $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
                            if ($tasks) {
                                return $this->sendHttpResponseSuccess($response, $tasks);
                            } else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                  //  }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /**
     * Description: fetch personal tasks that are not completed.
     */
    public function fetchPersonalTasks($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $taskSql = "SELECT cor_project_tasks.PROJECT_ID, cor_project_tasks.PARENT_TASK_ID, cor_project_tasks.TASK_ID, cor_project_tasks.TASK_TITLE, cor_project_tasks.TASK_DESCRIPTION, cor_project_tasks.CREATOR_ID, cor_project_tasks.ASSIGNED_ID, cor_project_tasks.DUE_DATE_DT, cor_project_tasks.CREATED_DATE, cor_project_tasks.DUE_DATE, cor_project_tasks.REPEAT_INTERVAL, cor_project_tasks.CC, cor_project_tasks.FILE_PATH, cor_project_tasks.TASK_STATUS, cor_project_tasks.UPDATED_DATE, cor_project_tasks.TASK_NOTIFICATION_STATUS, cor_project_tasks.QB_DIALOG_ID, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME  FROM cor_project_tasks 
                                        JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_project_tasks.ASSIGNED_ID
                                        WHERE (CREATOR_ID = :creatorId OR ASSIGNED_ID = :assignedId) AND PROJECT_ID IS NULL";
										
										//die( $taskSql);
                            $tasksStatement = $this->db->prepare($taskSql);
                            $tasksStatement->bindParam(":creatorId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $tasksStatement->bindParam(":assignedId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $tasksStatement->execute();
                            $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

                            foreach($tasks as $key => $data) {
                                $tasks[$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);

                                $creatorSql = "SELECT USER_ID, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM " . $this->getUserDetails() . " WHERE user_id = '" . $data['CREATOR_ID'] . "'";
                                $creatorStatement = $this->db->prepare($creatorSql);
                                $creatorStatement->execute();
                                $tasks[$key]['CREATOR'] = $creatorStatement->fetch(\PDO::FETCH_ASSOC);

                                $assigneeSql = "SELECT USER_ID, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM " . $this->getUserDetails() . " WHERE user_id = '" . $data['ASSIGNED_ID'] . "'";
                                $assigneeStatement = $this->db->prepare($assigneeSql);
                                $assigneeStatement->execute();
                                $tasks[$key]['ASSIGNEE'] = $assigneeStatement->fetch(\PDO::FETCH_ASSOC);

                                if (!empty($data['CC'])) {
                                    $tasks[$key]['CC'] = $this->getUsersFromCC($data['CC']);
                                }
								$tasks[$key]['TASK_NOTIFICATION_STATUS'] = $this->getTaskNotificationFromNocTable($params[ParamKeys::USER_ID],$data['TASK_ID']);
								
                            }
							//print_r($tasks); die();
                            if ($tasks) {
                                return $this->sendHttpResponseSuccess($response, $tasks);
                            } else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	 public function fetchOnlyPersonalTasks($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $taskSql = "SELECT cor_project_tasks.PROJECT_ID, cor_project_tasks.PARENT_TASK_ID, cor_project_tasks.TASK_ID, cor_project_tasks.TASK_TITLE, cor_project_tasks.TASK_DESCRIPTION, cor_project_tasks.CREATOR_ID, cor_project_tasks.ASSIGNED_ID, cor_project_tasks.DUE_DATE_DT, cor_project_tasks.CREATED_DATE, cor_project_tasks.DUE_DATE, cor_project_tasks.REPEAT_INTERVAL, cor_project_tasks.CC, cor_project_tasks.FILE_PATH, cor_project_tasks.TASK_STATUS, cor_project_tasks.UPDATED_DATE, cor_project_tasks.TASK_NOTIFICATION_STATUS, cor_project_tasks.QB_DIALOG_ID, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME  FROM cor_project_tasks 
                                        JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_project_tasks.ASSIGNED_ID
                                        WHERE (CREATOR_ID = :creatorId AND CREATOR_ID=ASSIGNED_ID) AND PROJECT_ID IS NULL ORDER BY cor_project_tasks.DUE_DATE ASC";
										
										//die( $taskSql);
                            $tasksStatement = $this->db->prepare($taskSql);
                            $tasksStatement->bindParam(":creatorId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            //$tasksStatement->bindParam(":assignedId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $tasksStatement->execute();
                            $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

                            foreach($tasks as $key => $data) {
                                $tasks[$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);

                                $creatorSql = "SELECT USER_ID, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM " . $this->getUserDetails() . " WHERE user_id = '" . $data['CREATOR_ID'] . "'";
                                $creatorStatement = $this->db->prepare($creatorSql);
                                $creatorStatement->execute();
                                $tasks[$key]['CREATOR'] = $creatorStatement->fetch(\PDO::FETCH_ASSOC);

                                $assigneeSql = "SELECT USER_ID, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM " . $this->getUserDetails() . " WHERE user_id = '" . $data['ASSIGNED_ID'] . "'";
                                $assigneeStatement = $this->db->prepare($assigneeSql);
                                $assigneeStatement->execute();
                                $tasks[$key]['ASSIGNEE'] = $assigneeStatement->fetch(\PDO::FETCH_ASSOC);

                                if (!empty($data['CC'])) {
                                    $tasks[$key]['CC'] = $this->getUsersFromCC($data['CC']);
                                }
								$tasks[$key]['TASK_NOTIFICATION_STATUS'] = $this->getTaskNotificationFromNocTable($params[ParamKeys::USER_ID],$data['TASK_ID']);
								
                            }
							//print_r($tasks); die();
                            if ($tasks) {
                                return $this->sendHttpResponseSuccess($response, $tasks);
                            } else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

 private function getTaskNotificationFromNocTable($userid,$taskid) {
			
			$taskSql = "SELECT STATUS FROM cor_notification_status WHERE USER_ID =:userId AND TASK_ID = :taskId";
            $tasksStatement = $this->db->prepare($taskSql);
            $tasksStatement->bindParam(":userId", $userid);
			$tasksStatement->bindParam(":taskId", $taskid);
            $tasksStatement->execute();
            $result = $tasksStatement->fetch(\PDO::FETCH_ASSOC);
			return isset($result['STATUS'])?$result['STATUS']:1;			
    }
	
    /*
     * Description: Delete task by taskId
     */
    public function deleteTask($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::TASK_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $proSql = $this->db->prepare('DELETE from '. $this->getTasks() . ' WHERE PARENT_TASK_ID = :taskId OR TASK_ID = :taskId');
                            $proSql->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_STR);
                            if($proSql->execute()){

                                $this->deleteTaskImages($params[ParamKeys::TASK_ID]);

                                return $this->sendHttpResponseMessage($response, "Task deleted successfully.");
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /**
     * Description: Get sub taskts of a task
     */
    public function fetchSubTasks($request, $response)
    {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::TASK_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $taskSql = "select * from ". $this->getTasks() . " where PARENT_TASK_ID = :taskId";
                            $tasksStatement = $this->db->prepare($taskSql);
                            $tasksStatement->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_STR);
                            $tasksStatement->execute();
                            $sub_tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
                            if($sub_tasks){
                                $resp['DATA'] = $sub_tasks;

                                foreach ($sub_tasks as $key => $data) {
                                    $resp['DATA'][$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);
                                }
                                return $this->sendHttpResponseSuccess($response, $resp);
                            } else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /**
     * Description: fetch all CC tasks
     */
	  public function fetchCCTasksUsers($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::USER_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
						
                       
                        $taskSql = "SELECT * FROM ". $this->getTasks() . " WHERE FIND_IN_SET(:userId, CC) AND TASK_STATUS != 'COMPLETED' AND DUE_DATE != 0 ";
						 if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "USERSLIST") {
									
										  $taskSql = "SELECT DISTINCT(cor_project_tasks.CREATOR_ID) AS CREATOR_ID, ptf_user_details.*  FROM cor_project_tasks, ptf_user_details WHERE ptf_user_details.USER_ID = cor_project_tasks.CREATOR_ID AND FIND_IN_SET(:userId, CC) AND cor_project_tasks.PROJECT_ID IS NULL ORDER BY cor_project_tasks.DUE_DATE ASC"; 

                                }
						
								
						
                        $tasksStatement = $this->db->prepare($taskSql);
						
                        $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                        $tasksStatement->execute();
                        $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

                        foreach ($tasks as $key => $data) {
							 if (!empty($data['TASK_ID'])) {
                            $tasks[$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);
							 }

                            if (!empty($data['CC'])) {
                                $tasks[$key]['CC'] = $this->getUsersFromCC($data['CC']);
                            }
                        }
							return $this->sendHttpResponseSuccess($response, $tasks);
                       
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	  public function fetchCCDueTasksUsers($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::USER_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
						
                        if (isset($params[ParamKeys::CURRENT_DATE])) {
                        $taskSql = "SELECT * FROM ". $this->getTasks() . " WHERE FIND_IN_SET(:userId, CC) AND TASK_STATUS != 'COMPLETED' AND DUE_DATE != 0 ";
						 if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "USERSLIST") {
									
										  $taskSql = "SELECT DISTINCT(cor_project_tasks.CREATOR_ID) AS CREATOR_ID, ptf_user_details.*  FROM cor_project_tasks, ptf_user_details WHERE ptf_user_details.USER_ID = cor_project_tasks.CREATOR_ID AND FIND_IN_SET(:userId, CC) AND cor_project_tasks.TASK_STATUS != 'COMPLETED' AND cor_project_tasks.DUE_DATE <= :dueDate AND cor_project_tasks.DUE_DATE != 0 AND cor_project_tasks.PROJECT_ID IS NULL ORDER BY cor_project_tasks.DUE_DATE ASC"; 

                                }
						}
							
						
                        $tasksStatement = $this->db->prepare($taskSql);
						if (isset($params[ParamKeys::CURRENT_DATE]) && $params[ParamKeys::CURRENT_DATE] !== "") {								
								
								$datenew = strtotime("+2 day", strtotime($params[ParamKeys::CURRENT_DATE]));
                                $tasksStatement->bindParam(":dueDate", $datenew, \PDO::PARAM_STR);
                            }
                        $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                        $tasksStatement->execute();
                        $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

                        foreach ($tasks as $key => $data) {
							 if (!empty($data['TASK_ID'])) {
                            $tasks[$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);
							 }

                            if (!empty($data['CC'])) {
                                $tasks[$key]['CC'] = $this->getUsersFromCC($data['CC']);
                            }
                        }
							return $this->sendHttpResponseSuccess($response, $tasks);
                       
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	
	 public function fetchCCDueTasksInterval($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::USER_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
						
                        if (isset($params[ParamKeys::CURRENT_DATE])) {
                        $taskSql = "SELECT * FROM ". $this->getTasks() . " WHERE FIND_IN_SET(:userId, CC) AND TASK_STATUS != 'COMPLETED' AND DUE_DATE != 0 ";
						 if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "USERSINTERVAL") {
									
										  $taskSql = "SELECT COUNT(*) as total, COUNT(IF(REPEAT_INTERVAL='',1,null)) as nonrepeated, COUNT(IF(REPEAT_INTERVAL !='',1,null)) as repeated FROM cor_project_tasks task  WHERE FIND_IN_SET(:userId, CC) AND task.TASK_STATUS != 'COMPLETED' AND task.DUE_DATE <= :dueDate AND task.DUE_DATE != 0 AND task.PROJECT_ID IS NULL ORDER BY task.DUE_DATE ASC"; 

                                }
						}
								
						
                        $tasksStatement = $this->db->prepare($taskSql);
						if (isset($params[ParamKeys::CURRENT_DATE]) && $params[ParamKeys::CURRENT_DATE] !== "") {								
								
								$datenew = strtotime("+2 day", strtotime($params[ParamKeys::CURRENT_DATE]));
                                $tasksStatement->bindParam(":dueDate", $datenew, \PDO::PARAM_STR);
                            }
                        $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                        $tasksStatement->execute();
                        $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

                        foreach ($tasks as $key => $data) {
							 if (!empty($data['TASK_ID'])) {
                            $tasks[$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);
							 }

                            if (!empty($data['CC'])) {
                                $tasks[$key]['CC'] = $this->getUsersFromCC($data['CC']);
                            }
                        }

                        if ($tasks) {
                            return $this->sendHttpResponseSuccess($response, $tasks);
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	public function fetchCCDueTasks($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::USER_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {

                        $taskSql = "SELECT * FROM ". $this->getTasks() . " WHERE FIND_IN_SET(:userId, CC) AND TASK_STATUS != 'COMPLETED' AND DUE_DATE != 0 ";
                        $tasksStatement = $this->db->prepare($taskSql);
                        $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                        $tasksStatement->execute();
                        $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

                        foreach ($tasks as $key => $data) {
                            $tasks[$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);

                            if (!empty($data['CC'])) {
                                $tasks[$key]['CC'] = $this->getUsersFromCC($data['CC']);
                            }
                        }

                        if ($tasks) {
                            return $this->sendHttpResponseSuccess($response, $tasks);
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	 public function fetchCCTasksInterval($request, $response) {
		 
		 
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::USER_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
						
                        $taskSql = "SELECT * FROM ". $this->getTasks() . " WHERE FIND_IN_SET(:userId, CC) AND TASK_STATUS != 'COMPLETED' AND DUE_DATE != 0 ";
						 if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "USERSINTERVAL") {
									
										  $taskSql = "SELECT COUNT(*) as total, COUNT(IF((REPEAT_INTERVAL=''AND DUE_DATE=0),1,NULL)) as todo, COUNT(IF((REPEAT_INTERVAL=''AND DUE_DATE!=0),1,NULL)) as nonrepeated, COUNT(IF((REPEAT_INTERVAL!=''),1,NULL)) as repeated FROM cor_project_tasks WHERE FIND_IN_SET(:userId, CC) AND PROJECT_ID IS NULL ORDER BY DUE_DATE ASC"; 

                                }
						
								
						
                        $tasksStatement = $this->db->prepare($taskSql);
						
                        $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                        $tasksStatement->execute();
                        $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

                        foreach ($tasks as $key => $data) {
							 if (!empty($data['TASK_ID'])) {
                            $tasks[$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);
							 }

                            if (!empty($data['CC'])) {
                                $tasks[$key]['CC'] = $this->getUsersFromCC($data['CC']);
                            }
                        }

                        if ($tasks) {
                            return $this->sendHttpResponseSuccess($response, $tasks);
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    
		 
    }
	// Orignal Class
    public function fetchCCTasks($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::USER_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {

                        $taskSql = "SELECT * FROM ". $this->getTasks() . " WHERE FIND_IN_SET(:userId, CC) AND PROJECT_ID IS NULL";
                        $tasksStatement = $this->db->prepare($taskSql);
                        $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                        $tasksStatement->execute();
                        $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

                        foreach ($tasks as $key => $data) {
                            $tasks[$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);

                            if (!empty($data['CC'])) {
                                $tasks[$key]['CC'] = $this->getUsersFromCC($data['CC']);
                            }
                        }

                        if ($tasks) {
                            return $this->sendHttpResponseSuccess($response, $tasks);
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	public function countTasksDD($request, $response) {
        try {
           // $params = $request->getQueryParams();
		   $params = $request->getParams(); 
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                           if (isset($params[ParamKeys::CURRENT_DATE])) {
                                if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "COUNTASSIGNMEDD") {
                                    $taskSql = "SELECT COUNT(TASK_ID) AS noOfTask FROM cor_project_tasks where CREATOR_ID != :userId AND ASSIGNED_ID = :userId AND DUE_DATE <= :dueDate AND TASK_STATUS != 'COMPLETED' AND DUE_DATE != 0 AND PROJECT_ID IS NULL";
									
									//die($taskSql);
                                } else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "COUNTPERSONALDD") {
                                    $taskSql = "SELECT COUNT(TASK_ID) AS noOfTask FROM cor_project_tasks where CREATOR_ID = :userId AND ASSIGNED_ID = :userId AND DUE_DATE <= :dueDate AND TASK_STATUS != 'COMPLETED' AND DUE_DATE != 0 AND PROJECT_ID IS NULL";
                                } else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "COUNTASSIGNEDOTHERDD") {
                                    $taskSql = "SELECT COUNT(TASK_ID) AS noOfTask FROM cor_project_tasks where CREATOR_ID = :userId AND ASSIGNED_ID != :userId AND DUE_DATE <= :dueDate AND TASK_STATUS != 'COMPLETED' AND DUE_DATE != 0 AND PROJECT_ID IS NULL";
									//die($taskSql);
                                }
								else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "CCTASKCOUNTDD") {
                                    $taskSql = "SELECT COUNT(TASK_ID) AS noOfTask FROM cor_project_tasks WHERE FIND_IN_SET(:userId, CC) AND DUE_DATE <= :dueDate AND TASK_STATUS != 'COMPLETED' AND DUE_DATE != 0 AND PROJECT_ID IS NULL";
									 
									 }
                            } 	
							 else {
                                $taskSql = "select * from ". $this->getTasks() . " where CREATOR_ID = :userId AND cor_project_tasks.PROJECT_ID IS NULL";
                            }
							
//die($taskSql);
                            $tasksStatement = $this->db->prepare($taskSql);

                            if (isset($params[ParamKeys::ASSIGNED_ID]) && $params[ParamKeys::ASSIGNED_ID] !== "") {
                                $tasksStatement->bindParam(":assignMeID", $params[ParamKeys::ASSIGNED_ID], \PDO::PARAM_STR);
                            }
							
							 if (isset($params[ParamKeys::STATUS]) && $params[ParamKeys::STATUS] !== "") {
                                $tasksStatement->bindParam(":status", $params[ParamKeys::STATUS], \PDO::PARAM_STR);
                            }

                            if (isset($params[ParamKeys::CURRENT_DATE]) && $params[ParamKeys::CURRENT_DATE] !== "") {								
								
								$datenew = strtotime("+2 day", strtotime($params[ParamKeys::CURRENT_DATE]));
                                $tasksStatement->bindParam(":dueDate", $datenew, \PDO::PARAM_STR);
                            }
//die(date("Y-m-d H:i:s",$datenew));

//die($datenew);
                            if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "PERSONAL") {
                                $tasksStatement->bindParam(":assignedId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            }

                            $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $tasksStatement->execute();
                            $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

                          

                            if ($tasks) {
                                return $this->sendHttpResponseSuccess($response, $tasks);
                            } else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	public function countTasks($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::USER_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
						 if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "ASSIGNMETASKCOUNT") {

                        $taskSql = "SELECT COUNT(TASK_ID) AS noOfTask FROM cor_project_tasks WHERE CREATOR_ID != :userId AND ASSIGNED_ID = :userId AND PROJECT_ID IS NULL";
						}
						else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "ASSIGNOTHERTASKCOUNT") {

                        $taskSql = "SELECT COUNT(TASK_ID) AS noOfTask FROM cor_project_tasks WHERE CREATOR_ID = :userId AND ASSIGNED_ID != :userId AND PROJECT_ID IS NULL";
						}
						 else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "PERSONALTASKCOUNT") {

                        $taskSql = "SELECT COUNT(TASK_ID) AS noOfTask FROM cor_project_tasks WHERE CREATOR_ID = :userId AND ASSIGNED_ID = :userId AND PROJECT_ID IS NULL";
						}
						 else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "CCTASKCOUNT") {

                        $taskSql = "SELECT COUNT(TASK_ID) AS noOfTask FROM cor_project_tasks WHERE FIND_IN_SET(:userId, CC) AND PROJECT_ID IS NULL";
						}
						else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "SHIPMENTCOUNT") {

                        $taskSql = "SELECT COUNT(TASK_ID) AS noOfTask FROM cor_project_tasks WHERE FIND_IN_SET(:userId, CC) AND PROJECT_ID IS NULL";
						}
						 else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "PROJECTCOUNT") {

                        $taskSql = "SELECT COUNT(TASK_ID) AS noOfTask FROM cor_project_tasks WHERE FIND_IN_SET(:userId, CC) AND PROJECT_ID IS NULL";
						}
						
						$tasksStatement = $this->db->prepare($taskSql);
                        $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                        $tasksStatement->execute();
                        $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);


                        if ($tasks) {
                            return $this->sendHttpResponseSuccess($response, $tasks);
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	 public function fetchCCTasksOnly($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::USER_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
						if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "CCTASKTODO") {

                      $taskSql = "SELECT cor_project_tasks.*, IF(cor_notification_status.STATUS IS NULL, 1, cor_notification_status.STATUS) as STATUS  FROM cor_project_tasks left join cor_notification_status on (cor_project_tasks.TASK_ID = cor_notification_status.TASK_ID ) WHERE FIND_IN_SET(:userId, CC) AND PROJECT_ID IS NULL AND cor_project_tasks.PROJECT_ID IS NULL AND cor_project_tasks.DUE_DATE = 0  AND cor_project_tasks.REPEAT_INTERVAL = ''";
						}
						else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "CCTASKNOREP") {

                        $taskSql = "SELECT cor_project_tasks.*, IF(cor_notification_status.STATUS IS NULL, 1, cor_notification_status.STATUS) as STATUS  FROM cor_project_tasks left join cor_notification_status on (cor_project_tasks.TASK_ID = cor_notification_status.TASK_ID ) WHERE FIND_IN_SET(:userId, CC) AND PROJECT_ID IS NULL AND cor_project_tasks.PROJECT_ID IS NULL AND cor_project_tasks.DUE_DATE != 0 AND cor_project_tasks.REPEAT_INTERVAL = ''";
						}
						else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "CCTASKREP") {

                        $taskSql = "SELECT cor_project_tasks.*, IF(cor_notification_status.STATUS IS NULL, 1, cor_notification_status.STATUS) as STATUS  FROM cor_project_tasks left join cor_notification_status on (cor_project_tasks.TASK_ID = cor_notification_status.TASK_ID ) WHERE FIND_IN_SET(:userId, CC) AND PROJECT_ID IS NULL AND cor_project_tasks.PROJECT_ID IS NULL AND cor_project_tasks.REPEAT_INTERVAL != ''";
						}
						$tasksStatement = $this->db->prepare($taskSql);
                        $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                        $tasksStatement->execute();
                        $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

                        foreach ($tasks as $key => $data) {
                            $tasks[$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);

                            if (!empty($data['CC'])) {
                                $tasks[$key]['CC'] = $this->getUsersFromCC($data['CC']);
                            }
                        }


                        if ($tasks) {
                            return $this->sendHttpResponseSuccess($response, $tasks);
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	public function fetchOnlyCCTasksDD($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::USER_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
						
                          if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "CCUSERDUE") {
                        $taskSql = "SELECT cor_project_tasks.*, IF(cor_notification_status.STATUS IS NULL, 1, cor_notification_status.STATUS) as STATUS  FROM cor_project_tasks left join cor_notification_status on (cor_project_tasks.TASK_ID = cor_notification_status.TASK_ID ) where cor_project_tasks.REPEAT_INTERVAL='' AND FIND_IN_SET(:userId, CC) AND cor_project_tasks.TASK_STATUS != 'COMPLETED' AND cor_project_tasks.DUE_DATE <= :dueDate AND cor_project_tasks.DUE_DATE != 0 AND cor_project_tasks.PROJECT_ID IS NULL ORDER BY cor_project_tasks.DUE_DATE ASC";
						  }
						  else if (isset($params[ParamKeys::TYPE]) && $params[ParamKeys::TYPE] === "CCUSERREP") {
                        $taskSql = "SELECT cor_project_tasks.*, IF(cor_notification_status.STATUS IS NULL, 1, cor_notification_status.STATUS) as STATUS  FROM cor_project_tasks left join cor_notification_status on (cor_project_tasks.TASK_ID = cor_notification_status.TASK_ID ) where cor_project_tasks.REPEAT_INTERVAL !=''AND FIND_IN_SET(:userId, CC) AND cor_project_tasks.TASK_STATUS != 'COMPLETED' AND cor_project_tasks.DUE_DATE <= :dueDate AND cor_project_tasks.DUE_DATE != 0 AND cor_project_tasks.PROJECT_ID IS NULL ORDER BY cor_project_tasks.DUE_DATE ASC";
						  }
                        $tasksStatement = $this->db->prepare($taskSql);
						if (isset($params[ParamKeys::CURRENT_DATE]) && $params[ParamKeys::CURRENT_DATE] !== "") {								
								
								$datenew = strtotime("+2 day", strtotime($params[ParamKeys::CURRENT_DATE]));
                                $tasksStatement->bindParam(":dueDate", $datenew, \PDO::PARAM_STR);
                            }
                        $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                        $tasksStatement->execute();
                        $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
						//die($taskSql);

                        foreach ($tasks as $key => $data) {
							 if (!empty($data['TASK_ID'])) {
                            $tasks[$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);
							 }

                            if (!empty($data['CC'])) {
                                $tasks[$key]['CC'] = $this->getUsersFromCC($data['CC']);
                            }
                        }
						return $this->sendHttpResponseSuccess($response, $tasks);
                       
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /**
     * Description: Remove from CC tasks
     */
    public function removeFromCCTask($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::USER_ID] == "" || $params[ParamKeys::TASK_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
                        $taskSql = "SELECT * FROM " . $this->getTasks() . " WHERE TASK_ID = :taskId";
                        $taskStatement = $this->db->prepare($taskSql);
                        $taskStatement->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_STR);
                        $taskStatement->execute();
                        $task = $taskStatement->fetch(\PDO::FETCH_ASSOC);

                        if(!empty($task)) {
                            $cc = explode(',', $task['CC']);

                            if (!empty($cc)) {
                                if (($key = array_search($params[ParamKeys::USER_ID], $cc)) !== false) {
                                    unset($cc[$key]);
                                }

                                $new_cc = implode(',', $cc);

                                $this->db->beginTransaction();
                                $taskUpdateStatement = $this->db->prepare('UPDATE ' . $this->getTasks() . ' set CC = :cc WHERE TASK_ID = :taskId');
                                $taskUpdateStatement->bindParam(":cc", $new_cc, \PDO::PARAM_STR);
                                $taskUpdateStatement->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_INT);
                                $taskUpdateStatement->execute();

                                if ($this->db->commit()) {
                                    return $this->sendHttpResponseSuccess($response, 'User has been removed from CC tasks');
                                }
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, 'No Task Found');
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /**
     * Description: Upload Task Images
     */
    public function uploadTaskImages($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::TASK_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
                        $uploadedFiles = $request->getUploadedFiles();
                        if (!empty($uploadedFiles)) {
                            foreach ($uploadedFiles['TASK_IMAGES'] as $uploadedFile) {
                                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                                    $filename = $this->moveUploadedFile('storage/tasks', $uploadedFile);

                                    $this->addTasksImages($filename, $params[ParamKeys::TASK_ID]);
                                }
                            }

                            return $this->sendHttpResponseSuccess($response, 'Task images uploaded');
                        } else {
                            return $this->sendHTTPResponseError($response, 'No images uploaded');
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /**
     * Description: Get project taskts by projectId
     */
    private function _get_project_tasks($projectId)
    {
        $taskSql = "select TASK_ID, PARENT_TASK_ID, PROJECT_ID, CREATOR_ID, TASK_TITLE, TASK_DESCRIPTION, DUE_DATE, TASK_STATUS from ". $this->getTasks() . " where PROJECT_ID = :projectId";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":projectId", $projectId);
        $tasksStatement->execute();
        return $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Description: Get personal taskts by userId
     */
    private function _get_personal_tasks($creatorId)
    {
        $taskSql = "select * from ". $this->getTasks() . " where CREATOR_ID = :creatorId AND PROJECT_ID is null";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":creatorId", $creatorId);
        $tasksStatement->execute();
        return $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Description: fetch all notifications on basis of userID
     */
    public function fetchNotifications($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $taskSql = "select * from ". $this->getNotifications() . " where RECEIVER_ID = :userId order by CREATED_AT desc limit 70";
                            $tasksStatement = $this->db->prepare($taskSql);
                            $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $tasksStatement->execute();
                            $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
                            if ($tasks) {
                                return $this->sendHttpResponseSuccess($response, $tasks);
                            } else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /*
     * Description: fetch project due tasks
     */
    public function fetchProjectDueTasks($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::USER_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
                        $taskSql = "SELECT * FROM ". $this->getTasks() . " WHERE PROJECT_ID IS NOT NULL AND FROM_UNIXTIME('DUE_DATE','%Y-%m-%d') <= :dueDate AND DUE_DATE != 0 AND (TASK_STATUS != 'COMPLETED' AND TASK_STATUS != 'CLOSED') AND ASSIGNED_ID = :userId";
                        $tasksStatement = $this->db->prepare($taskSql);
                        $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                        $tasksStatement->bindParam(":dueDate", date('Y-m-d'), \PDO::PARAM_STR);
                        $tasksStatement->execute();
                        $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

                        if($tasks){
                            return $this->sendHttpResponseSuccess($response, $tasks);
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS.'2');
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function taskNotificationTest($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::SENDER_ID] == "" || $params[ParamKeys::OBJECT_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {

                    $notification_type = "NEW_TASK_MEMBER_ADDED";
                    $notification = $this->notificationMsgMaping($notification_type, 1, 11, 'task');

                    if ($this->db) {
                        $this->db->beginTransaction();
                        $statementTask = $this->db->prepare('INSERT INTO  cor_notifications (NOTIFICATION, NOTIFICATION_TYPE, SENDER_ID, RECEIVER_ID, OBJECT_ID, OBJECT_TYPE) 
                                                                    VALUES(:notification, :notification_type, :senderId, :receiverId, :objectId, :objectType)');
                        $statementTask->bindParam(":notification", $notification);
                        $statementTask->bindParam(":notification_type", $notification_type);
                        $statementTask->bindParam(":senderId", $params[ParamKeys::SENDER_ID], \PDO::PARAM_STR);
                        $statementTask->bindParam(":receiverId", $params[ParamKeys::RECEIVER_ID], \PDO::PARAM_STR);
                        $statementTask->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
                        $statementTask->bindParam(":objectType", $params[ParamKeys::OBJECT_TYPE], \PDO::PARAM_STR);
                        $statementTask->execute();
                        $lastInsertId = $this->db->lastInsertId();
                        if ($this->db->commit()) {
                            // send notification
                            $msg = Notification::sendNotification();
                            return $this->sendHttpResponseMessage($response, "notification: " . $msg);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
//            $this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function changeTaskNotificationStatus($request, $response) {
		
		 try {
			 
			 // rafiq code changes
           //$params = $request->getParsedBody();
		   $params = $request->getParams();
			
			
            if (!empty($params) ) {
					
			
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::USER_ID] == "" || $params[ParamKeys::TASK_ID] == "" || $params[ParamKeys::STATUS] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
					
					
                    if ($this->db) {
                        $taskSql = "SELECT * FROM ". $this->getTasks() . " WHERE TASK_ID = :taskId AND ASSIGNED_ID = :userId LIMIT 1";
                        $tasksStatement = $this->db->prepare($taskSql);
                        $tasksStatement->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_STR);
                        $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                        $tasksStatement->execute();
                        $tasks = $tasksStatement->fetch(\PDO::FETCH_ASSOC);

                        if($tasks){
                            $taskNotificationSql = "SELECT * FROM cor_notification_status WHERE TASK_ID = :taskId AND USER_ID = :userId LIMIT 1";
                            $taskNotificationStatement = $this->db->prepare($taskNotificationSql);
                            $taskNotificationStatement->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_STR);
                            $taskNotificationStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $taskNotificationStatement->execute();
                            $result = $taskNotificationStatement->fetch(\PDO::FETCH_ASSOC);

                            if($result) {
                                $tasksStatement = $this->db->prepare('UPDATE cor_notification_status SET STATUS = :status WHERE USER_ID = :userId AND TASK_ID = :taskId');
                            } else {
                                $tasksStatement = $this->db->prepare('INSERT INTO cor_notification_status (USER_ID, TASK_ID, STATUS) VALUES (:userId, :taskId, :status)');
                            }

                            $this->db->beginTransaction();
                            $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $tasksStatement->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_STR);
                            $tasksStatement->bindParam(":status", $params[ParamKeys::STATUS], \PDO::PARAM_STR);
                            $tasksStatement->execute();

                            if ($this->db->commit()) {
                                return $this->sendHttpResponseMessage($response, "Task notification status changed");
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            //$this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function createQbGroup($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::TASK_ID] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
					
					
                    if ($this->db) {
                        $taskSql = "SELECT * FROM ". $this->getTasks() . " WHERE TASK_ID = :taskId LIMIT 1";
                        $tasksStatement = $this->db->prepare($taskSql);
                        $tasksStatement->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_STR);
                        $tasksStatement->execute();
                        $task = $tasksStatement->fetch(\PDO::FETCH_ASSOC);

                        if($task) {
                            if (empty($task['QB_DIALOG_ID'])) { 
                                $names[] = $this->getUser($task['CREATOR_ID'])['FULL_NAME']; 
                                $names[] = $this->getUser($task['ASSIGNED_ID'])['FULL_NAME']; 

                                $user_id[] = $this->getUser($task['CREATOR_ID'])['QB_ID'];
                                $user_id[] = $this->getUser($task['ASSIGNED_ID'])['QB_ID'];

                                if (!empty($task['CC'])) {
                                    foreach ($this->getUsersFromCC($task['CC']) as $cc_users) {
                                        $names[] = $cc_users['FULL_NAME']; 
                                        $user_id[] = $this->getUser($cc_users['USER_ID'])['QB_ID'];
                                    }
                                }

                                $this->qb_token = $this->_qbsession()->session->token;

                                if (!empty($this->qb_token)) {
                                    $qbGroup = $this->_createQbGroup(1, $names, $user_id);
                                }

                                if (!$qbGroup->errors) {
                                    $this->db->beginTransaction();
                                    $tasksStatement = $this->db->prepare('UPDATE ' . $this->getTasks() . ' SET QB_DIALOG_ID = :qbGroupId WHERE TASK_ID = :taskId');
                                    $tasksStatement->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_STR);
                                    $tasksStatement->bindParam(":qbGroupId", $qbGroup->_id, \PDO::PARAM_STR);
                                    $tasksStatement->execute();

                                    if ($this->db->commit()) {
                                        $taskSql = "SELECT * FROM " . $this->getTasks() . " WHERE TASK_ID = :taskId LIMIT 1";
                                        $tasksStatement = $this->db->prepare($taskSql);
                                        $tasksStatement->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_STR);
                                        $tasksStatement->execute();
                                        $task = $tasksStatement->fetch(\PDO::FETCH_ASSOC);

                                        return $this->sendHttpResponseSuccess($response, $task);
                                    }
                                } else {
                                    return $this->sendHTTPResponseError($response, $qbGroup->errors[0]);
                                }
                            } else {
                                return $this->sendHttpResponseSuccess($response, $task);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_TASK_FETCH);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            //$this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    private function getUsersFromCC($user_ids) {

        $user_ids = explode(',',$user_ids);
        $result = array();

        foreach ($user_ids as $user_id) {
            $taskSql = "SELECT USER_ID, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM ptf_user_details WHERE USER_ID IN (:userId)";
            $tasksStatement = $this->db->prepare($taskSql);
            $tasksStatement->bindParam(":userId", $user_id);
            $tasksStatement->execute();
            $result[] = $tasksStatement->fetch(\PDO::FETCH_ASSOC);
        }

        return $result;
    }

    private function moveUploadedFile($directory, $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = rand(10000000,99999999);
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }

    private function addTasksImages($filename, $taskId) {
        if (!is_null($filename)) {
            $this->db->beginTransaction();
            $stm = $this->db->prepare('INSERT cor_tasks_images (TASK_ID, TASK_IMAGE) 
                                                                        VALUES (:taskId, :taskImage)');
            $stm->bindParam(":taskId", $taskId, \PDO::PARAM_STR);
            $stm->bindParam(":taskImage", $filename, \PDO::PARAM_STR);
            $stm->execute();
            $this->db->commit();
        }
    }

    private function deleteTaskImages($taskid) {

        $shipmentImages = $this->db->prepare('SELECT TASK_IMAGE FROM cor_tasks_images WHERE TASK_ID = :taskId');
        $shipmentImages->bindParam(":taskId", $taskid, \PDO::PARAM_STR);
        $shipmentImages->execute();
        $images = $shipmentImages->fetchAll(\PDO::FETCH_ASSOC);

        if(!empty($images)) {
            foreach ($images as $image) {
                unlink('storage/tasks/' . $image['TASK_IMAGE']);
            }
        }

        $proShipmentImages = $this->db->prepare('DELETE from cor_tasks_images WHERE TASK_ID = :taskId');
        $proShipmentImages->bindParam(":taskId", $taskid, \PDO::PARAM_STR);
        $proShipmentImages->execute();
    }

    private function getTaskImages($taskid) {
        $taskImageSql = "SELECT TASK_IMAGE_ID, TASK_IMAGE FROM cor_tasks_images WHERE TASK_ID = :objectId";
        $taskImageStatement = $this->db->prepare($taskImageSql);
        $taskImageStatement->bindParam(":objectId", $taskid, \PDO::PARAM_STR);
        $taskImageStatement->execute();
        $taskImages = $taskImageStatement->fetchAll(\PDO::FETCH_ASSOC);

        $details = array();

        if(!empty($taskImages)) {
            foreach ($taskImages as $key => $image) {
                $details[$key]['TASK_IMAGE_ID'] = $image['TASK_IMAGE_ID'];
                $details[$key]['TASK_IMAGE'] = '/storage/tasks/' . $image['TASK_IMAGE'];
            }

            return $details;
        } else {
            return array();
        }
    }

    private function getUser($user_id) {
        $taskSql = "SELECT * FROM ptf_user_details WHERE USER_ID IN (:userId)";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":userId", $user_id);
        $tasksStatement->execute();
        $result = $tasksStatement->fetch(\PDO::FETCH_ASSOC);

        return $result;
    }
	 public function searchTasks($request, $response) {
		 
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "" || $params[ParamKeys::TYPE] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) 
						{
							if($params[ParamKeys::TYPE] == "sh")
							{
								
								$userSql = "select USER_ID, SUPER_ADMIN, IS_SHIPPER FROM ptf_user_details WHERE USER_ID = :userId";
								$userStatement = $this->db->prepare($userSql);
								$userStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
								$userStatement->execute();
								$user = $userStatement->fetch(\PDO::FETCH_ASSOC);
								if ($user) 
								{
									if($params[ParamKeys::TASK_TITLE] != "")
									{						
										
									if($user['SUPER_ADMIN'] == 1) {
										$shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID WHERE ( SHIPMENT_DESCRIPTION LIKE '%".$params[ParamKeys::TASK_TITLE]."%' OR  CUSTOMER_NAME LIKE '%".$params[ParamKeys::TASK_TITLE]."%' ) ORDER BY cor_shipments.SHIPMENT_CATEGORY ASC, cor_shipments.CREATED_DATE ASC";
										$shipmentStatement = $this->db->prepare($shipmentSql);
									} else if ($user['IS_SHIPPER'] == 1) {
										$shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID WHERE ( SHIPMENT_DESCRIPTION LIKE '%".$params[ParamKeys::TASK_TITLE]."%' OR  CUSTOMER_NAME LIKE '%".$params[ParamKeys::TASK_TITLE]."%' ) AND SHIPMENT_STATUS = 'APPROVED' AND (SHIPMENT_NUMBER = '' OR SHIPMENT_NUMBER IS NULL) ORDER BY cor_shipments.SHIPMENT_CATEGORY ASC, cor_shipments.CREATED_DATE ASC";
										$shipmentStatement = $this->db->prepare($shipmentSql);
									} else {
										$shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID WHERE ( SHIPMENT_DESCRIPTION LIKE '%".$params[ParamKeys::TASK_TITLE]."%' OR  CUSTOMER_NAME LIKE '%".$params[ParamKeys::TASK_TITLE]."%' ) AND CREATOR_ID = :userId ORDER BY cor_shipments.SHIPMENT_CATEGORY ASC, cor_shipments.CREATED_DATE ASC";
										$shipmentStatement = $this->db->prepare($shipmentSql);
										$shipmentStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
									}
									
									
									$shipmentStatement->execute();
									$shipments = $shipmentStatement->fetchAll(\PDO::FETCH_ASSOC);
	
									if(!empty($shipments)) {
										foreach ($shipments as $key => $shipment) {
											$shipmentImageSql = "SELECT * FROM cor_shipments_images WHERE SHIPMENT_ID = $shipment[SHIPMENT_ID]";
											$shipmentImageStatement = $this->db->prepare($shipmentImageSql);
											$shipmentImageStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
											$shipmentImageStatement->execute();
											$shipmentImages = $shipmentImageStatement->fetchAll(\PDO::FETCH_ASSOC);
	
											if (!empty($shipmentImages)) {
												foreach ($shipmentImages as $key1 => $image) {
													$shipments[$key]['images'][$key1]['SHIPMENT_IMAGE_ID'] = $image['SHIPMENT_IMAGE_ID'];
													$shipments[$key]['images'][$key1]['SHIPMENT_IMAGE'] = '/storage/shipments/' . $image['SHIPMENT_IMAGE'];
												}
											} else {
												$shipments[$key]['images'] = array();
											}
	
											$shipmentStatusSql = "SELECT * FROM cor_shipments_log WHERE SHIPMENT_ID = $shipment[SHIPMENT_ID]";
											$shipmentStatusStatement = $this->db->prepare($shipmentStatusSql);
											$shipmentStatusStatement->execute();
											$shipmentStatuses = $shipmentStatusStatement->fetchAll(\PDO::FETCH_ASSOC);
	
											if(!empty($shipmentStatuses)) {
												$shipments[$key]['logs'] = $shipmentStatuses;
											} else {
												$shipments[$key]['logs'] = array();
											}
										}
	
										return $this->sendHttpResponseSuccess($response, $shipments);
	
									} else {
										return $this->sendHttpResponseSuccess($response, $shipments);
									}
								
									}
									else
									{
									
									
									if($user['SUPER_ADMIN'] == 1) {
										$shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID  ORDER BY cor_shipments.SHIPMENT_CATEGORY ASC, cor_shipments.CREATED_DATE ASC";
										$shipmentStatement = $this->db->prepare($shipmentSql);
									} else if ($user['IS_SHIPPER'] == 1) {
										$shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID WHERE SHIPMENT_STATUS = 'APPROVED' AND (SHIPMENT_NUMBER = '' OR SHIPMENT_NUMBER IS NULL) ORDER BY cor_shipments.SHIPMENT_CATEGORY ASC, cor_shipments.CREATED_DATE ASC";
										$shipmentStatement = $this->db->prepare($shipmentSql);
									} else {
										$shipmentSql = "SELECT cor_shipments.*, MOBILE_NUMBER, EMAIL_ADDRESS, FULL_NAME FROM cor_shipments JOIN ptf_user_details ON ptf_user_details.USER_ID = cor_shipments.CREATOR_ID WHERE CREATOR_ID = :userId ORDER BY cor_shipments.SHIPMENT_CATEGORY ASC, cor_shipments.CREATED_DATE ASC";
										$shipmentStatement = $this->db->prepare($shipmentSql);
										$shipmentStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
									}
									
									
									$shipmentStatement->execute();
									$shipments = $shipmentStatement->fetchAll(\PDO::FETCH_ASSOC);
	
									if(!empty($shipments)) {
										foreach ($shipments as $key => $shipment) {
											$shipmentImageSql = "SELECT * FROM cor_shipments_images WHERE SHIPMENT_ID = $shipment[SHIPMENT_ID]";
											$shipmentImageStatement = $this->db->prepare($shipmentImageSql);
											$shipmentImageStatement->bindParam(":objectId", $params[ParamKeys::OBJECT_ID], \PDO::PARAM_STR);
											$shipmentImageStatement->execute();
											$shipmentImages = $shipmentImageStatement->fetchAll(\PDO::FETCH_ASSOC);
	
											if (!empty($shipmentImages)) {
												foreach ($shipmentImages as $key1 => $image) {
													$shipments[$key]['images'][$key1]['SHIPMENT_IMAGE_ID'] = $image['SHIPMENT_IMAGE_ID'];
													$shipments[$key]['images'][$key1]['SHIPMENT_IMAGE'] = '/storage/shipments/' . $image['SHIPMENT_IMAGE'];
												}
											} else {
												$shipments[$key]['images'] = array();
											}
	
											$shipmentStatusSql = "SELECT * FROM cor_shipments_log WHERE SHIPMENT_ID = $shipment[SHIPMENT_ID]";
											$shipmentStatusStatement = $this->db->prepare($shipmentStatusSql);
											$shipmentStatusStatement->execute();
											$shipmentStatuses = $shipmentStatusStatement->fetchAll(\PDO::FETCH_ASSOC);
	
											if(!empty($shipmentStatuses)) {
												$shipments[$key]['logs'] = $shipmentStatuses;
											} else {
												$shipments[$key]['logs'] = array();
											}
										}
	
										return $this->sendHttpResponseSuccess($response, $shipments);
	
									} else {
										return $this->sendHttpResponseSuccess($response, $shipments);
									}
								
									}
								} 
								else 
								{
									return $this->sendHttpResponseSuccess($response, 'User account does not exist');
								}
							
							}
							else
							{
														
							$taskSql = "";
							if($params[ParamKeys::TYPE] == "np")
							{								
								$taskSql = "SELECT * FROM `cor_project_tasks`, ptf_user_details WHERE CREATOR_ID = USER_ID AND (CREATOR_ID = ".$params[ParamKeys::USER_ID]." OR ASSIGNED_ID = ".$params[ParamKeys::USER_ID]." OR CC = ".$params[ParamKeys::USER_ID]." ) AND   `PROJECT_ID` IS NULL ";
							}
							else if($params[ParamKeys::TYPE] == "pr")
							{
								$taskSql = "SELECT * FROM `cor_project_tasks`, ptf_user_details WHERE CREATOR_ID = USER_ID AND (CREATOR_ID = ".$params[ParamKeys::USER_ID]." OR ASSIGNED_ID = ".$params[ParamKeys::USER_ID]." OR CC = ".$params[ParamKeys::USER_ID]." ) AND  `PROJECT_ID` IS NOT NULL ";
							}
							
							if($params[ParamKeys::TASK_TITLE] != "")
							{								
								$taskSql .= " AND TASK_TITLE LIKE '%".$params[ParamKeys::TASK_TITLE]."%'";
							}
							
							$tasksStatement = $this->db->prepare($taskSql);							
							$tasksStatement->execute();
                            $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);							
							foreach ($tasks as $key => $data) 
							{	
								$tasks[$key]['TYPE'] = '';
								$tasks[$key]['TYPE_ORDER'] = 0;
                                $tasks[$key]['IMAGES'] = $this->getTaskImages($data['TASK_ID']);
								if(($tasks[$key]['CREATOR_ID']!=$tasks[$key]['ASSIGNED_ID']) && ($tasks[$key]['ASSIGNED_ID'] == $params[ParamKeys::USER_ID]))
								{									
									$tasks[$key]['TYPE'] = 'Assign to Me Task';
									$tasks[$key]['TYPE_ORDER'] = 1;
								}
								if(($tasks[$key]['CREATOR_ID']==$tasks[$key]['ASSIGNED_ID']) && ($tasks[$key]['CREATOR_ID'] == $params[ParamKeys::USER_ID])) 
								{
									$tasks[$key]['TYPE'] = 'Personal Task';	
									$tasks[$key]['TYPE_ORDER'] = 2;
								}
								if(($tasks[$key]['CREATOR_ID']!=$tasks[$key]['ASSIGNED_ID']) && ($tasks[$key]['CREATOR_ID'] == $params[ParamKeys::USER_ID]))
								{
									$tasks[$key]['TYPE'] = 'Assign to Others Task';
									$tasks[$key]['TYPE_ORDER'] = 3;
								}								
								if(($tasks[$key]['CREATOR_ID'] != $params[ParamKeys::USER_ID]) && ($tasks[$key]['CC'] == $params[ParamKeys::USER_ID]))
								{
									$tasks[$key]['TYPE'] = 'CC Task';
									$tasks[$key]['TYPE_ORDER'] = 4;
								}
																
							}
							return $this->sendHttpResponseSuccess($response, $tasks);   
						
							}
						}
						else 
						{
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
        //this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    
	 }
}