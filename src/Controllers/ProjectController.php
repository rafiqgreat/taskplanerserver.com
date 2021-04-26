<?php
/**
 * Created by IntelliJ IDEA.
 * User: nayab
 * Date: 23-Sep-17
 * Time: 3:39 AM
 */

namespace Controllers;

use Constants\Messages;
use Constants\ParamKeys;
use Psr\Container\ContainerInterface;
use Validators\AuthValidatorController;
use Utils\CommonUtils;

class ProjectController extends ControllerBase
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
    public function createProject($request, $response, $arg) {
        try {
            $params = $request->getParsedBody();
            $inactiveUsers = array();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::PROJECT_NAME] == "" || $params[ParamKeys::CREATOR_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $stmDuplicateProject = $this->db->prepare('select * from '. $this->getProjects() . ' where PROJECT_NAME = :projectName AND CREATOR_ID = :creatorId');
                            $stmDuplicateProject->bindParam(":projectName", $params[ParamKeys::PROJECT_NAME], \PDO::PARAM_STR);
                            $stmDuplicateProject->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                            $stmDuplicateProject->execute();
                            $projectData = $stmDuplicateProject->fetch(\PDO::FETCH_ASSOC);
                            if ($projectData) {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DUPLICATE_PROJECT);
                            } else {
                                $this->db->beginTransaction();
                                $statementProject = $this->db->prepare('INSERT INTO ' . $this->getProjects() . ' (PROJECT_NAME, PROJECT_DESCRIPTION, CREATOR_ID, DUE_DATE) VALUES(:projectName, :description, :userId, :dueDate)');
                                $statementProject->bindParam(":projectName", $params[ParamKeys::PROJECT_NAME], \PDO::PARAM_STR);
                                $statementProject->bindParam(":description", $params[ParamKeys::PROJECT_DESCRIPTION], \PDO::PARAM_STR);
                                $statementProject->bindParam(":userId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                $statementProject->bindParam(":dueDate", $params[ParamKeys::DUE_DATE], \PDO::PARAM_STR);
                                $statementProject->execute();
                                $projectId = $this->db->lastInsertId();
                                $this->db->commit();
                                $projectStatement = $this->db->prepare('select * from '. $this->getProjects() . ' where PROJECT_ID = :projectId');
                                $projectStatement->bindParam(":projectId", $projectId, \PDO::PARAM_STR);
                                $projectStatement->execute();
                                $projectDetailsData = $projectStatement->fetch(\PDO::FETCH_ASSOC);
                                if ($projectDetailsData) {
                                    $usersList = explode(',', $params[ParamKeys::MEMBERS_LIST]);
                                    foreach ($usersList as $user) {
                                        $statementUser = $this->db->prepare('select * from '. $this->getUserDetailsVW() . ' where MOBILE_NUMBER = :mobileNumber');										
                                        $statementUser->bindParam(":mobileNumber", $user, \PDO::PARAM_STR);
                                        $statementUser->execute();
                                        $userData = $statementUser->fetch(\PDO::FETCH_ASSOC);
                                        if ($userData) {
                                            $this->db->beginTransaction();
                                            $statementMembers = $this->db->prepare('INSERT INTO ' . $this->getProjectMembersTBL() . ' (PROJECT_ID, USER_ID, PROJECT_ROLE, ADD_BY_ID) VALUES(:projectId, :userId, :role, :addedById)');
                                            $statementMembers->bindParam(":projectId", $projectId, \PDO::PARAM_STR);
                                            $statementMembers->bindParam(":userId", $userData['USER_ID'], \PDO::PARAM_STR);
                                            $role = "";
                                            if ($userData['USER_ID'] == $params[ParamKeys::CREATOR_ID]) {
                                                $role = "OWNER";
                                            } else {
                                                $role = "MEMBER";
                                            }
                                            $statementMembers->bindParam(":role", $role, \PDO::PARAM_STR);
                                            $statementMembers->bindParam(":addedById", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                            $statementMembers->execute();
                                            $this->db->commit();
                                            $device_tokens[] = $this->getDeviceToken($userData['USER_ID']);
                                            $this->sendNotification($params[ParamKeys::CREATOR_ID], $userData['USER_ID'],
                                            $projectId, 'project', 'NEW_PROJECT_MEMBER_ADDED', $device_tokens, $params[ParamKeys::PROJECT_NAME]);
                                        }else{
                                            $authToken = CommonUtils::build()->generateCode();
                                            $this->db->beginTransaction();
                                            $sqlDetails = 'INSERT INTO ' . $this->getUserDetails() . ' (MOBILE_NUMBER, ACCOUNT_STATUS) VALUES(:mobileEmail, :account_status)';
                                            //print_r($sqlDetails);exit();
                                            $account_status = "INACTIVE";
                                            $statementDetails = $this->db->prepare($sqlDetails);
                                            $statementDetails->bindParam(":mobileEmail", $user, \PDO::PARAM_STR);
                                            $statementDetails->bindParam(":account_status", $account_status, \PDO::PARAM_STR);
                                            $rowUserDetails = $statementDetails->execute();
                                            $userId = $this->db->lastInsertId();
                                            //print_r($userId);exit();
                                            if ($rowUserDetails) {
                                                $statementSecret = $this->db->prepare('INSERT INTO ' . $this->getUserSecrets() . ' (USER_ID, PASSCODE) VALUES(:userId, :passCode)');
                                                $statementSecret->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                                $passCode="ABC123";
                                                $statementSecret->bindParam(":passCode", $passCode, \PDO::PARAM_STR);
                                                $statementSecret->execute();
                                                if ($userId > 0) {
                                                    $statementAuth = $this->db->prepare('INSERT INTO ' . $this->getUserAuths() . ' (USER_ID, AUTH_TOKEN, SESSION_ID) VALUES (:userId, :authToken, :sessionId)');
                                                    $statementAuth->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                                    $statementAuth->bindParam(":authToken", $authToken, \PDO::PARAM_STR);
													$cvar = CommonUtils::build()->generateCode(32);
                                                    $statementAuth->bindParam(":sessionId",$cvar , \PDO::PARAM_STR);
                                                    $statementAuth->execute();
                                                }
                                            }
                                            if ($userId > 0) {
                                                $statementMembers = $this->db->prepare('INSERT INTO ' . $this->getProjectMembersTBL() . ' (PROJECT_ID, USER_ID, PROJECT_ROLE, ADD_BY_ID) VALUES(:projectId, :userId, :role, :addedById)');
                                                $statementMembers->bindParam(":projectId", $projectId, \PDO::PARAM_STR);
                                                $statementMembers->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                                $role = "MEMBER";
                                                $statementMembers->bindParam(":role", $role, \PDO::PARAM_STR);
                                                $statementMembers->bindParam(":addedById", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                                $statementMembers->execute();
                                                $this->db->commit();
                                            } 
                                        }
                                    }
                                    $projectMembersStat = $this->db->prepare('select CREATOR_ID, USER_ID, MEMBER_ID, USER_NAME, FULL_NAME, PROFILE_FILENAME, PROJECT_ROLE, ACCOUNT_STATUS from '. $this->getProjectsMembersVW() . ' where PROJECT_ID = :projectid');
                                    $projectMembersStat->bindParam(":projectid", $projectId, \PDO::PARAM_STR);
                                    $projectMembersStat->execute();
                                    $members = $projectMembersStat->fetchAll(\PDO::FETCH_ASSOC);
                                    $projectDetailsData['MEMBERS'] = $members;
                                    //$projectDetailsData['INACTIVE'] = $inactiveUsers;
                                    return $this->sendHttpResponseSuccess($response, $projectDetailsData);
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
//            $this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

/////////// changed by rafiq rafiqgreat@gmail.com +923334292580
    public function createProjectRafiq($request, $response, $arg) {
        try {
            $params = $request->getParsedBody();
            $inactiveUsers = array();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::PROJECT_NAME] == "" || $params[ParamKeys::CREATOR_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $stmDuplicateProject = $this->db->prepare('select * from '. $this->getProjects() . ' where PROJECT_NAME = :projectName AND CREATOR_ID = :creatorId');
                            $stmDuplicateProject->bindParam(":projectName", $params[ParamKeys::PROJECT_NAME], \PDO::PARAM_STR);
                            $stmDuplicateProject->bindParam(":creatorId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_INT);
                            $stmDuplicateProject->execute();
                            $projectData = $stmDuplicateProject->fetch(\PDO::FETCH_ASSOC);
                            if ($projectData) {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DUPLICATE_PROJECT);
                            } else {								
                                $this->db->beginTransaction();
                                $statementProject = $this->db->prepare('INSERT INTO ' . $this->getProjects() . ' (PROJECT_NAME, PROJECT_DESCRIPTION, CREATOR_ID, DUE_DATE) VALUES(:projectName, :description, :userId, :dueDate)');
								
								//die('INSERT INTO ' . $this->getProjects() . ' (PROJECT_NAME, PROJECT_DESCRIPTION, CREATOR_ID, DUE_DATE) VALUES("'.$params[ParamKeys::PROJECT_NAME].'", "'.$params[ParamKeys::PROJECT_DESCRIPTION].'", "'.$params[ParamKeys::CREATOR_ID].'", "'.$params[ParamKeys::DUE_DATE].'")');
                                $statementProject->bindParam(":projectName", $params[ParamKeys::PROJECT_NAME], \PDO::PARAM_STR);
                                $statementProject->bindParam(":description", $params[ParamKeys::PROJECT_DESCRIPTION], \PDO::PARAM_STR);
                                $statementProject->bindParam(":userId", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_INT);
                                $statementProject->bindParam(":dueDate", $params[ParamKeys::DUE_DATE], \PDO::PARAM_STR);
                                $statementProject->execute();
                                $projectId = $this->db->lastInsertId();
                                $this->db->commit();
                                $projectStatement = $this->db->prepare('select * from '. $this->getProjects() . ' where PROJECT_ID = :projectId');
                                $projectStatement->bindParam(":projectId", $projectId, \PDO::PARAM_STR);
                                $projectStatement->execute();
                                $projectDetailsData = $projectStatement->fetch(\PDO::FETCH_ASSOC);
                                if ($projectDetailsData) {
                                    $usersList = explode(',', $params[ParamKeys::MEMBERS_LIST]);
                                    foreach ($usersList as $user) {
										$user = str_replace(' ', '', $user);
                                        $statementUser = $this->db->prepare("select * from ". $this->getUserDetailsVW() . " where MOBILE_NUMBER LIKE '%".$user."%'");										
                                        //$statementUser->bindParam(":mobileNumber", $user, \PDO::PARAM_STR);
                                        $statementUser->execute();
                                        $userData = $statementUser->fetch(\PDO::FETCH_ASSOC);
                                        if ($userData) {
                                            $this->db->beginTransaction();
                                            $statementMembers = $this->db->prepare('INSERT INTO ' . $this->getProjectMembersTBL() . ' (PROJECT_ID, USER_ID, PROJECT_ROLE, ADD_BY_ID) VALUES(:projectId, :userId, :role, :addedById)');
                                            $statementMembers->bindParam(":projectId", $projectId, \PDO::PARAM_STR);
                                            $statementMembers->bindParam(":userId", $userData['USER_ID'], \PDO::PARAM_STR);
                                            $role = "";
                                            if ($userData['USER_ID'] == $params[ParamKeys::CREATOR_ID]) {
                                                $role = "OWNER";
                                            } else {
                                                $role = "MEMBER";
                                            }
                                            $statementMembers->bindParam(":role", $role, \PDO::PARAM_STR);
                                            $statementMembers->bindParam(":addedById", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                            $statementMembers->execute();
                                            $this->db->commit();
                                            $device_tokens[] = $this->getDeviceToken($userData['USER_ID']);
                                            $this->sendNotification($params[ParamKeys::CREATOR_ID], $userData['USER_ID'],
                                            $projectId, 'project', 'NEW_PROJECT_MEMBER_ADDED', $device_tokens, $params[ParamKeys::PROJECT_NAME]);
                                        }else{
                                            $authToken = CommonUtils::build()->generateCode();
                                            $this->db->beginTransaction();
                                            $sqlDetails = 'INSERT INTO ' . $this->getUserDetails() . ' (MOBILE_NUMBER, ACCOUNT_STATUS) VALUES(:mobileEmail, :account_status)';
                                            //print_r($sqlDetails);exit();
                                            $account_status = "INACTIVE";
                                            $statementDetails = $this->db->prepare($sqlDetails);
                                            $statementDetails->bindParam(":mobileEmail", $user, \PDO::PARAM_STR);
                                            $statementDetails->bindParam(":account_status", $account_status, \PDO::PARAM_STR);
                                            $rowUserDetails = $statementDetails->execute();
                                            $userId = $this->db->lastInsertId();
                                            //print_r($userId);exit();
                                            if ($rowUserDetails) {
                                                $statementSecret = $this->db->prepare('INSERT INTO ' . $this->getUserSecrets() . ' (USER_ID, PASSCODE) VALUES(:userId, :passCode)');
                                                $statementSecret->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                                $passCode="ABC123";
                                                $statementSecret->bindParam(":passCode", $passCode, \PDO::PARAM_STR);
                                                $statementSecret->execute();
                                                if ($userId > 0) {
                                                    $statementAuth = $this->db->prepare('INSERT INTO ' . $this->getUserAuths() . ' (USER_ID, AUTH_TOKEN, SESSION_ID) VALUES (:userId, :authToken, :sessionId)');
                                                    $statementAuth->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                                    $statementAuth->bindParam(":authToken", $authToken, \PDO::PARAM_STR);
													$cvar = CommonUtils::build()->generateCode(32);
                                                    $statementAuth->bindParam(":sessionId",$cvar , \PDO::PARAM_STR);
                                                    $statementAuth->execute();
                                                }
                                            }
                                            if ($userId > 0) {
                                                $statementMembers = $this->db->prepare('INSERT INTO ' . $this->getProjectMembersTBL() . ' (PROJECT_ID, USER_ID, PROJECT_ROLE, ADD_BY_ID) VALUES(:projectId, :userId, :role, :addedById)');
                                                $statementMembers->bindParam(":projectId", $projectId, \PDO::PARAM_STR);
                                                $statementMembers->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                                $role = "MEMBER";
                                                $statementMembers->bindParam(":role", $role, \PDO::PARAM_STR);
                                                $statementMembers->bindParam(":addedById", $params[ParamKeys::CREATOR_ID], \PDO::PARAM_STR);
                                                $statementMembers->execute();
                                                $this->db->commit();
                                            } 
                                        }
                                    }
                                    $projectMembersStat = $this->db->prepare('select CREATOR_ID, USER_ID, MEMBER_ID, USER_NAME, FULL_NAME, PROFILE_FILENAME, PROJECT_ROLE, ACCOUNT_STATUS from '. $this->getProjectsMembersVW() . ' where PROJECT_ID = :projectid');
                                    $projectMembersStat->bindParam(":projectid", $projectId, \PDO::PARAM_STR);
                                    $projectMembersStat->execute();
                                    $members = $projectMembersStat->fetchAll(\PDO::FETCH_ASSOC);
                                    $projectDetailsData['MEMBERS'] = $members;
                                    //$projectDetailsData['INACTIVE'] = $inactiveUsers;
                                    return $this->sendHttpResponseSuccess($response, $projectDetailsData);
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
//            $this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    /*
     * Delete project and its data from tasks, and views by constraints.
     */
    public function deleteProject($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::PROJECT_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            //$proSql = $this->db->prepare("UPDATE ". $this->getProjects() . " set PROJECT_STATUS = 'CLOSED' WHERE PROJECT_ID = :projectId ");
                            //$proSql->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                            $proSql = $this->db->prepare('DELETE from '. $this->getProjects() . ' WHERE PROJECT_ID = :projectId');
                            $proSql->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                            if($proSql->execute()){
                                return $this->sendHttpResponseMessage($response, "Project deleted successfully.");
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

    public function updateProjectStatus($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::PROJECT_ID] == "" || $params[ParamKeys::STATUS] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $this->db->beginTransaction();
                            $tasksStatement = $this->db->prepare('UPDATE '. $this->getProjects().' set PROJECT_STATUS = :status where PROJECT_ID = :projectId');
                            $tasksStatement->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                            $tasksStatement->bindParam(":status", $params[ParamKeys::STATUS], \PDO::PARAM_STR);
                            $tasksStatement->execute();
                            if ($this->db->commit()) {
                                return $this->sendHttpResponseMessage($response, "Project updated successfully.");
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

    public function addProjectMembers($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::PROJECT_ID] == "" || $params[ParamKeys::ADD_BY_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $usersList = explode(',', $params[ParamKeys::MEMBERS_LIST]);
                            foreach ($usersList as $user) {
                                $user = '%'.substr($user, -10);
                                $statementUser = $this->db->prepare('select * from '. $this->getUserDetails() . ' where MOBILE_NUMBER LIKE :mobileNumber');
                                $statementUser->bindParam(":mobileNumber", $user, \PDO::PARAM_STR);
                                $statementUser->execute();
                                $userData = $statementUser->fetch(\PDO::FETCH_ASSOC);
                                if ($userData) {
                                    $stmDuplicate = $this->db->prepare('select * from '. $this->getProjectMembersTBL() . ' where PROJECT_ID = :projectid and MEMBER_ID = :userid');
                                    $stmDuplicate->bindParam(":projectid", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                                    $stmDuplicate->bindParam(":userid", $userData['USER_ID'], \PDO::PARAM_STR);
                                    $stmDuplicate->execute();
                                    $member = $stmDuplicate->fetch(\PDO::FETCH_ASSOC);
                                    if(!$member){
                                        $this->db->beginTransaction();
                                        $statementMembers = $this->db->prepare('INSERT INTO ' . $this->getProjectMembersTBL() . ' (PROJECT_ID, USER_ID, PROJECT_ROLE, ADD_BY_ID) VALUES(:projectId, :userId, :role, :addedById)');
                                        $statementMembers->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                                        $statementMembers->bindParam(":userId", $userData['USER_ID'], \PDO::PARAM_STR);
                                        $role = "";
                                        if ($userData['USER_ID'] == $params[ParamKeys::ADD_BY_ID]) {
                                            $role = "OWNER";
                                        } else {
                                            $role = "MEMBER";
                                        }
                                        $statementMembers->bindParam(":role", $role, \PDO::PARAM_STR);
                                        $statementMembers->bindParam(":addedById", $params[ParamKeys::ADD_BY_ID], \PDO::PARAM_STR);
                                        $statementMembers->execute();
                                        $this->db->commit();
                                        $device_tokens[] = $this->getDeviceToken($userData['USER_ID']);
                                        $this->sendNotification($params[ParamKeys::ADD_BY_ID], $userData['USER_ID'],
                                        $params[ParamKeys::PROJECT_ID], 'project', 'NEW_PROJECT_MEMBER_ADDED', $device_tokens);
                                    }
                                }else{
                                    $authToken = CommonUtils::build()->generateCode();
                                    $sessionId  = CommonUtils::build()->generateCode(32);
                                    $this->db->beginTransaction();
                                    $sqlDetails = 'INSERT INTO ' . $this->getUserDetails() . ' (MOBILE_NUMBER, ACCOUNT_STATUS) VALUES(:mobileEmail, :account_status)';
                                    //print_r($sqlDetails);exit();
                                    $account_status = "INACTIVE";
                                    $statementDetails = $this->db->prepare($sqlDetails);
                                    $statementDetails->bindParam(":mobileEmail", $user, \PDO::PARAM_STR);
                                    $statementDetails->bindParam(":account_status", $account_status, \PDO::PARAM_STR);
                                    $rowUserDetails = $statementDetails->execute();
                                    $userId = $this->db->lastInsertId();
                                    //print_r($userId);exit();
                                    if ($rowUserDetails) {
                                        $statementSecret = $this->db->prepare('INSERT INTO ' . $this->getUserSecrets() . ' (USER_ID, PASSCODE) VALUES(:userId, :passCode)');
                                        $statementSecret->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                        $passCode="ABC123";
                                        $statementSecret->bindParam(":passCode", $passCode, \PDO::PARAM_STR);
                                        $statementSecret->execute();
                                        if ($userId > 0) {
                                            $statementAuth = $this->db->prepare('INSERT INTO ' . $this->getUserAuths() . ' (USER_ID, AUTH_TOKEN, SESSION_ID) VALUES (:userId, :authToken, :sessionId)');
                                            $statementAuth->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                            $statementAuth->bindParam(":authToken", $authToken, \PDO::PARAM_STR);
                                            $statementAuth->bindParam(":sessionId", $sessionId, \PDO::PARAM_STR);
                                            $statementAuth->execute();
                                        }
                                    }
                                    if ($userId > 0) {
                                        $statementMembers = $this->db->prepare('INSERT INTO ' . $this->getProjectMembersTBL() . ' (PROJECT_ID, USER_ID, PROJECT_ROLE, ADD_BY_ID) VALUES(:projectId, :userId, :role, :addedById)');
                                        $statementMembers->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                                        $statementMembers->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                        $role = "MEMBER";
                                        $statementMembers->bindParam(":role", $role, \PDO::PARAM_STR);
                                        $statementMembers->bindParam(":addedById", $params[ParamKeys::ADD_BY_ID], \PDO::PARAM_STR);
                                        $statementMembers->execute();
                                        $this->db->commit();
                                    } 
                                }
                            }

                            $projectMembersStat = $this->db->prepare('select CREATOR_ID, USER_ID, MEMBER_ID, USER_NAME, FULL_NAME, PROFILE_FILENAME, PROJECT_ROLE, ACCOUNT_STATUS from '. $this->getProjectsMembersVW() . ' where PROJECT_ID = :projectid');
                            $projectMembersStat->bindParam(":projectid", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                            $projectMembersStat->execute();
                            $members = $projectMembersStat->fetchAll(\PDO::FETCH_ASSOC);
                            $projectDetailsData['MEMBERS'] = $members;
                            return $this->sendHttpResponseSuccess($response, $projectDetailsData);
                            
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

    public function updateProjectMemberRole($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                if ($params[ParamKeys::PROJECT_ID] == "" || $params[ParamKeys::USER_ID] == "" || $params[ParamKeys::PROJECT_ROLE] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
                        $this->db->beginTransaction();
                        $tasksStatement = $this->db->prepare('UPDATE '. $this->getProjectMembersTBL().' SET PROJECT_ROLE = :projectRole WHERE PROJECT_ID = :projectId AND USER_ID = :userId');
                        $tasksStatement->bindParam(":projectRole", $params[ParamKeys::PROJECT_ROLE], \PDO::PARAM_STR);
                        $tasksStatement->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_INT);
                        $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_INT);
                        $tasksStatement->execute();
                        if ($this->db->commit()) {
                            return $this->sendHttpResponseMessage($response, "Project role updated successfully.");
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
    /*public function getUserProjects($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $sql = "SELECT a.PROJECT_ID, a.PROJECT_NAME, a.PROJECT_DESCRIPTION, a.PROJECT_STATUS, 
                                           a.PRIVACY_TYPE, a.CREATOR_ID, a.MODERATOR_ID, a.DUE_DATE, a.CREATED_DATE, 
                                           b.USER_ID, b.FULL_NAME, b.ACCOUNT_STATUS, b.MOBILE_NUMBER AS USER_NAME, b.PROFILE_FILENAME
                                    FROM cor_project_details a
                                    JOIN ptf_user_details b ON a.CREATOR_ID = b.USER_ID" ;

                            //$stmDuplicate = $this->db->prepare('select * from '. $this->getProjectsMembersVW() . ' where CREATOR_ID = :userid');
                            $stmDuplicate = $this->db->prepare($sql . ' WHERE a.CREATOR_ID = :userid');
                            $stmDuplicate->bindParam(":userid", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $stmDuplicate->execute();
                            $projects = $stmDuplicate->fetchAll(\PDO::FETCH_ASSOC);
                            $data = [];
                            if ($projects) {
                                foreach ($projects as $project){
                                    //$stm = $this->db->prepare("select * from ". $this->getProjectsMembersVW() . " where PROJECT_ID = :projectid ");
                                    $sql2 = "SELECT a.PROJECT_ID, a.USER_ID AS MEMBER_ID, a.PROJECT_ROLE, a.USER_STATUS AS MEMBER_STATUS,
                                              b.USER_ID, b.FULL_NAME, b.ACCOUNT_STATUS, b.MOBILE_NUMBER AS USER_NAME
                                             FROM cor_project_members a 
                                             JOIN ptf_user_details b ON a.USER_ID = b.USER_ID";
                                    $stm = $this->db->prepare($sql2 . " WHERE `a`.`PROJECT_ID` = :projectid ");
                                    $stm->bindParam(":projectid", $project['PROJECT_ID'], \PDO::PARAM_STR);
                                    $stm->execute();
                                    $members = $stm->fetchAll(\PDO::FETCH_ASSOC);
                                    $project['MEMBERS'] = $members;
                                    $data[] = $project;
                                }

                                //return $this->sendJsonResponse($response, "SUCCESS", false, $data);
                                return $this->sendHttpResponseSuccess($response, $data);
                            }else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_FETCH);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }*/
	 public function getUserMoible($request, $response) {
        try {
            //$params = $request->getQueryParams();
			$params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $sql = "SELECT MOBILE_NUMBER FROM ptf_user_details WHERE USER_ID = :userid LIMIT 1";
                            $stmMobile = $this->db->prepare($sql);                        
                            $stmMobile->bindParam(":userid", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $stmMobile->execute();
							$userMobile = $stmMobile->fetch(\PDO::FETCH_ASSOC);
							if ($userMobile) 
							{
								 return $this->sendHttpResponseSuccess($response, $userMobile);
							}
							else
							{
								 return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_MEMBER_FETCH);
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
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }


 public function getRegisteredUsers($request, $response) {
        try {
            //$params = $request->getQueryParams();
			 $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::STATUS] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $stmUsers = $this->db->prepare('select * from '. $this->getUserDetails() . ' where ACCOUNT_STATUS = :accStatus');
                            $stmUsers->bindParam(":accStatus", $params[ParamKeys::STATUS], \PDO::PARAM_STR);
                            $stmUsers->execute();
                            $data = $stmUsers->fetchAll(\PDO::FETCH_ASSOC);
                            if ($data) {
                                return $this->sendHttpResponseSuccess($response, $data);
                            }else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_MEMBER_FETCH);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_CODE);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	
	 public function getRegisteredUsersSearch($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::STATUS] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $stmUsers = $this->db->prepare('select * from '. $this->getUserDetails() . ' where ACCOUNT_STATUS = :accStatus AND FULL_NAME LIKE :fname');
                            $stmUsers->bindParam(":accStatus", $params[ParamKeys::STATUS], \PDO::PARAM_STR);
							$stmUsers->bindParam(":fname", $params[ParamKeys::FULL_NAME], \PDO::PARAM_STR);
                            $stmUsers->execute();
                            $data = $stmUsers->fetchAll(\PDO::FETCH_ASSOC);
							if ($data) 
							{
								foreach($data as $row) 
								{
									$countryResult[] = $row["FULL_NAME"];
								}
							echo json_encode($countryResult);
							}	
	
                          
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_CODE);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }



    
    public function getUserProjects($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $sql = "SELECT a.PROJECT_ID, a.PROJECT_NAME, a.PROJECT_DESCRIPTION, a.PROJECT_STATUS, 
                                           a.PRIVACY_TYPE, a.CREATOR_ID, a.MODERATOR_ID, a.DUE_DATE, a.CREATED_DATE, 
                                           b.USER_ID, b.FULL_NAME, b.ACCOUNT_STATUS, b.MOBILE_NUMBER AS USER_NAME, b.PROFILE_FILENAME
                                    FROM cor_project_members c 
                                    LEFT JOIN cor_project_details a ON c.PROJECT_ID = a.PROJECT_ID
                                    LEFT JOIN ptf_user_details b ON a.CREATOR_ID = b.USER_ID" ;

                            //$stmDuplicate = $this->db->prepare('select * from '. $this->getProjectsMembersVW() . ' where CREATOR_ID = :userid');
                            $stmDuplicate = $this->db->prepare($sql . ' WHERE c.USER_ID = :userid');
                        
                            $stmDuplicate->bindParam(":userid", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $stmDuplicate->execute();
                            $projects = $stmDuplicate->fetchAll(\PDO::FETCH_ASSOC);
                            $data = [];
                            if ($projects) {
                                foreach ($projects as $project){
                                    //$stm = $this->db->prepare("select * from ". $this->getProjectsMembersVW() . " where PROJECT_ID = :projectid ");
                                    $sql2 = "SELECT a.PROJECT_ID, a.USER_ID AS MEMBER_ID, a.PROJECT_ROLE, a.USER_STATUS AS MEMBER_STATUS,
                                              b.USER_ID, b.FULL_NAME, b.ACCOUNT_STATUS, b.MOBILE_NUMBER AS USER_NAME
                                             FROM cor_project_members a 
                                             JOIN ptf_user_details b ON a.USER_ID = b.USER_ID";
                                    $stm = $this->db->prepare($sql2 . " WHERE `a`.`PROJECT_ID` = :projectid ");
                                    $stm->bindParam(":projectid", $project['PROJECT_ID'], \PDO::PARAM_STR);
                                    $stm->execute();
                                    $members = $stm->fetchAll(\PDO::FETCH_ASSOC);
                                    $project['MEMBERS'] = $members;
                                    $data[] = $project;
                                }

                                //return $this->sendJsonResponse($response, "SUCCESS", false, $data);
                                return $this->sendHttpResponseSuccess($response, $data);
                            }else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_FETCH);
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
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
	
	  public function getProjectDetail($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::PROJECT_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
							
							 $taskSql = "SELECT * FROM cor_project_details WHERE PROJECT_ID = :projectId";
							 $tasksStatement = $this->db->prepare($taskSql);
							 $tasksStatement->bindParam(":projectId", $params[ParamKeys::PROJECT_ID]);
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
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function getProjectMemberss($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::PROJECT_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $stmDuplicate = $this->db->prepare('select * from '. $this->getProjectsMembersVW() . ' where PROJECT_ID = :userid');
                            $stmDuplicate->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                            $stmDuplicate->execute();
                            $data = $stmDuplicate->fetchAll(\PDO::FETCH_ASSOC);
                            if ($data) {
                                return $this->sendHttpResponseSuccess($response, $data);
                            }else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_MEMBER_FETCH);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                /*} else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_CODE);
                }*/
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function addProjectMember($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::PROJECT_ID] == "" || $params[ParamKeys::USER_EMAIL] == "" || $params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $stmUser = $this->db->prepare('select * from '. $this->getUserDetails() . ' where MOBILE_NUMBER = :userEmail');
                            $stmUser->bindParam(":userEmail", $params[ParamKeys::USER_EMAIL], \PDO::PARAM_STR);
                            $stmUser->execute();
                            $member = $stmUser->fetch(\PDO::FETCH_ASSOC);
                            if(!$member){
                                return $this->sendHTTPResponseError($response, "User not found.");
                            } else {
                                // check duplicate user entry
                                $stmDuplicate = $this->db->prepare('select * from '. $this->getProjectsMembersVW() . ' where USER_NAME = :userEmail and PROJECT_ID = :projectid');
                                $stmDuplicate->bindParam(":userEmail", $params[ParamKeys::USER_EMAIL], \PDO::PARAM_STR);
                                $stmDuplicate->bindParam(":projectid", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                                $stmDuplicate->execute();
                                $data = $stmDuplicate->fetchAll(\PDO::FETCH_ASSOC);
                                if (!$data) {
                                    // insert new member
                                    $this->db->beginTransaction();
                                    $statementAuth = $this->db->prepare('INSERT INTO cor_project_members (PROJECT_ID, USER_ID, ADD_BY_ID, PROJECT_ROLE) VALUES(:projectId, :userId, :addedById, :projectRole)');
                                    $statementAuth->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                                    $statementAuth->bindParam(":projectRole", $params[ParamKeys::PROJECT_ROLE], \PDO::PARAM_STR);
                                    $statementAuth->bindParam(":userId", $member['USER_ID'], \PDO::PARAM_STR);
                                    $statementAuth->bindParam(":addedById", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                                    $statementAuth->execute();
                                    $projectId = $this->db->lastInsertId();
                                    $this->db->commit();
                                    if($projectId > 0){
                                        $stm = $this->db->prepare('select * from '. $this->getProjectsMembersVW() . ' where MEMBER_ID = :memberid and PROJECT_ID = :projectid');
                                        $stm->bindParam(":memberid", $member['USER_ID'], \PDO::PARAM_STR);
                                        $stm->bindParam(":projectid", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                                        $stm->execute();
                                        $data = $stm->fetch(\PDO::FETCH_ASSOC);
                                        return $this->sendHttpResponseSuccess($response, $data);
                                    }else {
                                        return $this->sendHTTPResponseError($response, "Something went wrong.");
                                    }
                                }else {
                                    return $this->sendHttpResponseSuccess($response, "User already added.");
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
            $this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
    
    public function deleteMember($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::PROJECT_ID] == "" || $params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $this->db->beginTransaction();
                            //$tasksStatement = $this->db->prepare('UPDATE '. $this->getProjectMembersTBL().' set USER_STATUS = :status where PROJECT_ID = :projectId AND USER_ID = :userId');
                            $tasksStatement = $this->db->prepare('DELETE FROM '. $this->getProjectMembersTBL().' where PROJECT_ID = :projectId AND USER_ID = :userId');
                            $tasksStatement->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                            $tasksStatement->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            //$status = 'INACTIVE';
                            //$tasksStatement->bindParam(":status", $status, \PDO::PARAM_STR);

                            $tasksStatement->execute();
                            if ($this->db->commit()) {
                                //DELETE ALL TASK OF USER
                                $userTasks = $this->db->prepare('DELETE FROM cor_project_tasks where PROJECT_ID = :projectId AND (CREATOR_ID = :creatorId OR ASSIGNED_ID = :assignedId)');
                                $userTasks->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                                $userTasks->bindParam(":creatorId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                                $userTasks->bindParam(":assignedId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                                $userTasks->execute();
                                return $this->sendHttpResponseMessage($response, "Member deleted successfully.");
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
}