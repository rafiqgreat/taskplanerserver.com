<?php
/**
 * Created by IntelliJ IDEA.
 * User: nayab
 * Date: 16-Oct-17
 * Time: 5:43 PM
 */

namespace Controllers;

use Constants\Messages;
use Constants\ParamKeys;
use Psr\Container\ContainerInterface;
use Validators\AuthValidatorController;

class UserController extends ControllerBase
{
    protected $container;
    protected $db;

    // constructor receives container instance
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->db = $this->container->primary;
    }

    public function verifyMemberUser($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params['AUTH_TOKEN'], $params['SESSION_ID'])) {
                    if ($params[ParamKeys::PROJECT_ID] == "" || $params[ParamKeys::CREATOR_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            if (is_numeric($params[ParamKeys::USER_NAME])) {
                                $sqlCheck = 'SELECT USER_ID, FULL_NAME, PROFILE_FILENAME, ACCOUNT_STATUS FROM ' . $this->getUserDetails() . ' WHERE MOBILE_NUMBER = :userName';
                                $column = "MOBILE_NUMBER";
                            } else {
                                $sqlCheck = 'SELECT USER_ID, FULL_NAME, PROFILE_FILENAME, ACCOUNT_STATUS FROM ' . $this->getUserDetails() . ' WHERE EMAIL_ADDRESS = :userName';
                                $column = "EMAIL_ADDRESS";
                            }
                            $statementCheck = $this->db->prepare($sqlCheck);
                            $statementCheck->bindParam(":userName", $params[ParamKeys::USER_NAME], \PDO::PARAM_STR);
                            $statementCheck->execute();
                            $rowCheck = $statementCheck->fetch(\PDO::FETCH_ASSOC);

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

    public function getUserDetail($request, $response, $args) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params['AUTH_TOKEN'], $params['SESSION_ID'])) {
                    if ($args['username'] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $sqlCheck = 'SELECT USER_ID, FULL_NAME, USER_NAME, PROFILE_FILENAME FROM ' . $this->getUserDetailsVW() . ' WHERE USER_NAME = :userName';
                            $statementCheck = $this->db->prepare($sqlCheck);
                            $statementCheck->bindParam(":userName", $args['username'], \PDO::PARAM_STR);
                            $statementCheck->execute();
                            $data = $statementCheck->fetch(\PDO::FETCH_ASSOC);
                            if ($data) {
                                return $this->sendHttpResponseSuccess($response, $data);
                            } else {
                                return $this->sendHttpResponseMessage($response, "NO_USER");
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

    public function updateSettings($request, $response, $args)
    {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params['AUTH_TOKEN'], $params['SESSION_ID'])) {
                    if ($params[ParamKeys::USER_ID] == "" || $params[ParamKeys::SETTING_TYPE] == "" || $params[ParamKeys::SETTING_STATUS] == "" ) {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $sqlCheck = 'SELECT * FROM cor_settings WHERE USER_ID = :userId AND SETTING_TYPE = :settingType';
                            $statementCheck = $this->db->prepare($sqlCheck);
                            $statementCheck->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $statementCheck->bindParam(":settingType", $params[ParamKeys::SETTING_TYPE], \PDO::PARAM_STR);
                            $statementCheck->execute();
                            $setting = $statementCheck->fetch(\PDO::FETCH_ASSOC);
                            if ($setting) {
                                // update
                                $this->db->beginTransaction();
                                $stm = $this->db->prepare('UPDATE cor_settings set SETTING_STATUS = :settingStatus where USER_ID = :userId AND SETTING_TYPE = :settingType');
                                $stm->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                                $stm->bindParam(":settingType", $params[ParamKeys::SETTING_TYPE], \PDO::PARAM_STR);
                                $stm->bindParam(":settingStatus", $params[ParamKeys::SETTING_STATUS], \PDO::PARAM_STR);
                                $stm->execute();
                                if ($this->db->commit()) {
                                    return $this->sendHttpResponseMessage($response, "Settings successfully saved.");
                                }else {
                                    return $this->sendHttpResponseMessage($response, "Settings not saved.");
                                }
                            } else {
                                // add new
                                $this->db->beginTransaction();
                                $statementAuth = $this->db->prepare('INSERT INTO cor_settings (USER_ID, SETTING_TYPE, SETTING_STATUS) VALUES(:userId, :settingType, :settingStatus)');
                                $statementAuth->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                                $statementAuth->bindParam(":settingType", $params[ParamKeys::SETTING_TYPE], \PDO::PARAM_STR);
                                $statementAuth->bindParam(":settingStatus", $params[ParamKeys::SETTING_STATUS], \PDO::PARAM_STR);
                                $statementAuth->execute();
                                $lastInsertId = $this->db->lastInsertId();
                                $this->db->commit();
                                if($lastInsertId > 0){
                                    return $this->sendHttpResponseMessage($response, "Settings successfully saved.");
                                }else {
                                    return $this->sendHttpResponseMessage($response, "Settings not saved.");
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

    public function sendMessage($request, $response)
    {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params['AUTH_TOKEN'], $params['SESSION_ID'])) {
                    if ($params[ParamKeys::SENDER_ID] == "" || $params[ParamKeys::RECEIVER_ID] == "" || $params[ParamKeys::PROJECT_ID] == ""
                        || $params[ParamKeys::TASK_ID] == "" || $params[ParamKeys::MESSAGE] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {

                        if ($this->db) {
                            $this->db->beginTransaction();
                            $stm = $this->db->prepare('INSERT INTO cor_chats  (SENDER_ID, RECEIVER_ID, PROJECT_ID, TASK_ID, MESSAGE) VALUES(:senderId, :receiverId, :projectId, :taskId, :message)');
                            $stm->bindParam(":senderId", $params[ParamKeys::SENDER_ID], \PDO::PARAM_STR);
                            $stm->bindParam(":receiverId", $params[ParamKeys::RECEIVER_ID], \PDO::PARAM_STR);
                            $stm->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                            $stm->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_STR);
                            $stm->bindParam(":message", $params[ParamKeys::MESSAGE], \PDO::PARAM_STR);
                            $stm->execute();
                            $lastInsertId = $this->db->lastInsertId();
                            if ($this->db->commit()) {
                                $device_tokens[] = $this->getDeviceToken($params[ParamKeys::RECEIVER_ID]);
                                $this->sendNotification($params[ParamKeys::SENDER_ID], $params[ParamKeys::RECEIVER_ID],
                                $lastInsertId, 'chats', 'NEW_MESSAGE_SENDED', $device_tokens, $params[ParamKeys::MESSAGE]);
                                $data = [];
                                return $this->sendHttpResponseSuccess($response, $data);
                            }else {
                                return $this->sendHTTPResponseError($response, "Something went wrong");
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

    public function fetchMessages($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "" || $params[ParamKeys::PROJECT_ID] == "" || $params[ParamKeys::TASK_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $stmDuplicate = $this->db->prepare('select * from cor_chats where (SENDER_ID = :userId OR RECEIVER_ID = :userId) AND PROJECT_ID = :projectId AND TASK_ID = :taskId ');
                            $stmDuplicate->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $stmDuplicate->bindParam(":projectId", $params[ParamKeys::PROJECT_ID], \PDO::PARAM_STR);
                            $stmDuplicate->bindParam(":taskId", $params[ParamKeys::TASK_ID], \PDO::PARAM_STR);
                            $stmDuplicate->execute();
                            $chats = $stmDuplicate->fetchAll(\PDO::FETCH_ASSOC);
                            //if ($chats) {
                                //return $this->sendJsonResponse($response, "SUCCESS", false, $chats);
                                return $this->sendHttpResponseSuccess($response, $chats);
//
//                            }else {
//                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PROJECT_FETCH);
//                            }
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

    public function updateUser($request, $response, $args)
    {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params['AUTH_TOKEN'], $params['SESSION_ID'])) {
                    if ($params[ParamKeys::USER_ID] == "" || $params[ParamKeys::FULL_NAME] == "" ) {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            // upload image
                            $filename = null;
                                if($_FILES){
                                    $uploadedFiles = $request->getUploadedFiles();
                                    $uploadedFile = $uploadedFiles['USER_IMG'];
                                    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                                        $filename = $this->moveUploadedFile('storage/members', $uploadedFile);
                                    }
                                }
                                $this->db->beginTransaction();
                                if(!is_null($filename)){
                                    $stm = $this->db->prepare('UPDATE ptf_user_details set FULL_NAME = :fullName, PROFILE_FILENAME = :fileName where USER_ID = :userId');
                                    $stm->bindParam(":fileName", $filename, \PDO::PARAM_STR);
                                }else {
                                    $stm = $this->db->prepare('UPDATE ptf_user_details set FULL_NAME = :fullName where USER_ID = :userId');
                                }
                                $stm->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                                $stm->bindParam(":fullName", $params[ParamKeys::FULL_NAME], \PDO::PARAM_STR);
                                $stm->execute();
                                if ($this->db->commit()) {
                                    // get user
                                    $stm = $this->db->prepare('select * from ptf_user_details_vw where USER_ID = :userId ');
                                    $stm->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                                    $stm->execute();
                                    $user = $stm->fetch(\PDO::FETCH_ASSOC);
                                    return $this->sendHttpResponseSuccess($response, $user);
                                }else {
                                    return $this->sendHttpResponseMessage($response, "Profile not updated.");
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

	public function updateDeviceToken($request, $response, $args)
	{
		try {
			$params = $request->getParsedBody();
			if (!empty($params) ) {
				//if (AuthValidatorController::validateAuth($this->db, $params['AUTH_TOKEN'], $params['SESSION_ID'])) {
					if ($params[ParamKeys::USER_ID] == "" || $params[ParamKeys::DEVICE_TYPE] == "" || $params[ParamKeys::DEVICE_TOKEN] == "" ) {
						return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
					} else {
						if ($this->db) {
							$this->db->beginTransaction();
							$stm = $this->db->prepare('UPDATE ptf_user_details set DEVICE_TYPE = :deviceType, DEVICE_TOKEN = :deviceToken  where USER_ID = :userId');
							$stm->bindParam(":deviceType", $params[ParamKeys::DEVICE_TYPE], \PDO::PARAM_STR);
							$stm->bindParam(":deviceToken", $params[ParamKeys::DEVICE_TOKEN], \PDO::PARAM_STR);
							$stm->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
							$stm->execute();
							if ($this->db->commit()) {
								// get user
								$stm = $this->db->prepare('select * from ptf_user_details where USER_ID = :userId ');
								$stm->bindParam(":userId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
								$stm->execute();
								$user = $stm->fetch(\PDO::FETCH_ASSOC);
								return $this->sendHttpResponseSuccess($response, $user);
							}else {
								return $this->sendHttpResponseMessage($response, "device not updated.");
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

    public function moveUploadedFile($directory, $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        //echo $directory; exit;
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }

    public function verifyPersonContact($request, $response) {
        try {
            $params = $request->getParsedBody();
            $results = array();
            if (!empty($params) ) {
                if (AuthValidatorController::validateAuth($this->db, $params['AUTH_TOKEN'], $params['SESSION_ID'])) {
                    if ($params[ParamKeys::MOBILE_NUMBER] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $mobileNumbers = explode(',', $params[ParamKeys::MOBILE_NUMBER]);
                            foreach ($mobileNumbers as $numbers) {
                                $mobileNumber = substr($numbers, -10);
                                $mobileNumber = '%' . $mobileNumber;

                                $sqlCheck = 'SELECT USER_ID, MOBILE_NUMBER, FULL_NAME, PROFILE_FILENAME, ACCOUNT_STATUS FROM ptf_user_details WHERE MOBILE_NUMBER LIKE :mobileNumber';
                                $statementCheck = $this->db->prepare($sqlCheck);
                                $statementCheck->bindParam(":mobileNumber", $mobileNumber, \PDO::PARAM_STR);
                                $statementCheck->execute();
                                $data = $statementCheck->fetch(\PDO::FETCH_ASSOC);

                                if(!empty($data)) {
                                    $results[] = $data;
                                }
                            }
                            if ($results) {
                                return $this->sendHttpResponseSuccess($response, $results);
                            } else {
                                return $this->sendHttpResponseMessage($response, "NO_USER");
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                        }
                    }
                } else {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_HTTP_PARAMS);
                }
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function userSignupQuickblox($request, $response) {
        //get all users from database
        $sqlCheck = 'SELECT USER_ID, MOBILE_NUMBER, FULL_NAME, ACCOUNT_STATUS FROM ' . $this->getUserDetails() . ' WHERE USER_ID NOT IN (44,74) ORDER BY USER_ID';
        $statementCheck = $this->db->prepare($sqlCheck);
        $statementCheck->execute();
        $users = $statementCheck->fetchAll(\PDO::FETCH_ASSOC);

        $this->qb_token = $this->_qbsession()->session->token;

        if(!empty($this->qb_token)) {

            foreach ($users as $user) {
                $qb_user_data = $this->_qbsignup($user);

                if ($qb_user_data) {
                    $this->db->beginTransaction();

                    $stm = $this->db->prepare('UPDATE ptf_user_details SET QB_ID = :qb_id, QB_USER_LOGIN = :qb_user_login, QB_PASSWORD = :qb_password WHERE USER_ID = :userId');
                    $stm->bindParam(":qb_id", $qb_user_data->user->id, \PDO::PARAM_STR);
                    $stm->bindParam(":qb_user_login", $qb_user_data->user->login, \PDO::PARAM_STR);
                    $stm->bindParam(":qb_password", $qb_user_data->user->login, \PDO::PARAM_STR);
                    $stm->bindParam(":userId", $user['USER_ID'], \PDO::PARAM_STR);
                    $stm->execute();

                    $this->db->commit();
                }
            }

            return $this->sendHttpResponseSuccess($response, 'USER QB data updated');
        }
    }
}