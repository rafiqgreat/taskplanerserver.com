<?php
/**
 * Created by IntelliJ IDEA.
 * User: nayab
 * Date: 22-Sep-17
 * Time: 9:32 PM
 */
namespace Controllers;

use Constants\Messages;
use Constants\ParamKeys;
use \Psr\Container\ContainerInterface;
use Utils\CommonUtils;

class AuthenticationController extends ControllerBase
{
    protected $container;
    protected $db;

    // constructor receives container instance
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->db = $this->container->primary;
    }

    public function loginUser2($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params)) {
                if ($params[ParamKeys::FULL_NAME] == "" || $params[ParamKeys::MOBILE_NUMBER] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
                        $sqlVerify = 'SELECT * FROM ' . $this->getUserDetailsVW() . ' WHERE FULL_NAME = :fullName AND MOBILE_NUMBER = :phoneNumber';
                        $statement = $this->db->prepare($sqlVerify);
                        $statement->bindParam(":fullName", $params['FULL_NAME'], \PDO::PARAM_STR);
                        $statement->bindParam(":phoneNumber", $params['MOBILE_NUMBER'], \PDO::PARAM_STR);
                        $statement->execute();
                        $row = $statement->fetch(\PDO::FETCH_ASSOC);
                        if ($row) {
                                $this->db->beginTransaction();
                                $sessionId = CommonUtils::build()->generateCode(32);
                                $statementUpdate = $this->db->prepare('UPDATE ' . $this->getUserAuths() . ' SET SESSION_ID = :sessionId, UPDATE_DATE = :updatedDate WHERE USER_ID = :userId');
                                $statementUpdate->bindParam(":userId", $row['USER_ID'], \PDO::PARAM_STR);
                                $statementUpdate->bindParam(":sessionId", $sessionId, \PDO::PARAM_STR);
                                $statementUpdate->bindParam(":updatedDate", CommonUtils::build()->getCurrentDateTime(), \PDO::PARAM_STR);
                                $statementUpdate->execute();
                                $this->db->commit();
                                $data = array(
                                        ParamKeys::USER_ID => $row['USER_ID'],
                                        ParamKeys::FULL_NAME => $row['FULL_NAME'],
                                        ParamKeys::USER_NAME => $row['USER_NAME'],
                                        ParamKeys::PROFILE_FILENAME => $row['PROFILE_FILENAME'],
                                        ParamKeys::AUTH_TOKEN => $row['AUTH_TOKEN'],
                                        ParamKeys::SESSION_ID => $sessionId
                                    );
                                return $this->sendHttpResponseSuccess($response, $data);
                        } else {
                            $this->db->beginTransaction();
                                $sessionId = CommonUtils::build()->generateCode(32);
                                $statementUpdate = $this->db->prepare('UPDATE ' . $this->getUserAuths() . ' SET SESSION_ID = :sessionId, UPDATE_DATE = :updatedDate WHERE USER_ID = :userId');
                                $statementUpdate->bindParam(":userId", $row['USER_ID'], \PDO::PARAM_STR);
                                $statementUpdate->bindParam(":sessionId", $sessionId, \PDO::PARAM_STR);
                                $statementUpdate->bindParam(":updatedDate", CommonUtils::build()->getCurrentDateTime(), \PDO::PARAM_STR);
                                $statementUpdate->execute();
                                $this->db->commit();
                                $sessionId = CommonUtils::build()->generateCode(32);
                                $statementUpdate = $this->db->prepare('UPDATE ' . $this->getUserAuths() . ' SET SESSION_ID = :sessionId, UPDATE_DATE = :updatedDate WHERE USER_ID = :userId');
                                $statementUpdate->bindParam(":userId", $row['USER_ID'], \PDO::PARAM_STR);
                                $statementUpdate->bindParam(":sessionId", $sessionId, \PDO::PARAM_STR);
                                $statementUpdate->bindParam(":updatedDate", CommonUtils::build()->getCurrentDateTime(), \PDO::PARAM_STR);
                                $statementUpdate->execute();
                                $this->db->commit();
                                $data = array(
                                        ParamKeys::USER_ID => $row['USER_ID'],
                                        ParamKeys::FULL_NAME => $row['FULL_NAME'],
                                        ParamKeys::USER_NAME => $row['USER_NAME'],
                                        ParamKeys::PROFILE_FILENAME => $row['PROFILE_FILENAME'],
                                        ParamKeys::AUTH_TOKEN => $row['AUTH_TOKEN'],
                                        ParamKeys::SESSION_ID => $sessionId
                                    );
                                return $this->sendHttpResponseSuccess($response, $data);
                            //return $this->sendHTTPResponseError($response, Messages::MSG_ERR_INVALID_USER);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage());
        }
    }

    public function loginUser($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params)) {
                if ($params[ParamKeys::USER_NAME] == "" || $params[ParamKeys::PASS_CODE] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
                        $sqlVerify = 'SELECT * FROM ' . $this->getUserDetailsVW() . ' WHERE USER_NAME = :userName AND PASSCODE = :passCode';
                        $statement = $this->db->prepare($sqlVerify);
                        $statement->bindParam(":userName", $params['USER_NAME'], \PDO::PARAM_STR);
                        $statement->bindParam(":passCode", $params['PASS_CODE'], \PDO::PARAM_STR);
                        $statement->execute();
                        $row = $statement->fetch(\PDO::FETCH_ASSOC);
                        if ($row) {
                            if ($row['ACCOUNT_STATUS'] == 'PENDING') {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_ACCOUNT_PENDING);
                            } else if ($row['ACCOUNT_STATUS'] == 'DISABLED') {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_ACCOUNT_DISABLED);
                            } else if ($row['ACCOUNT_STATUS'] == 'INACTIVE') {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_ACCOUNT_INACTIVE);
                            } else {
                                $this->db->beginTransaction();
                                $sessionId = CommonUtils::build()->generateCode(32);
                                $statementUpdate = $this->db->prepare('UPDATE ' . $this->getUserAuths() . ' SET SESSION_ID = :sessionId, UPDATE_DATE = :updatedDate WHERE USER_ID = :userId');
                                $statementUpdate->bindParam(":userId", $row['USER_ID'], \PDO::PARAM_STR);
                                $statementUpdate->bindParam(":sessionId", $sessionId, \PDO::PARAM_STR);
                                $statementUpdate->bindParam(":updatedDate", CommonUtils::build()->getCurrentDateTime(), \PDO::PARAM_STR);
                                $statementUpdate->execute();
                                $this->db->commit();

                                $data = array(
                                        ParamKeys::USER_ID => $row['USER_ID'],
                                        ParamKeys::FULL_NAME => $row['FULL_NAME'],
                                        ParamKeys::USER_NAME => $row['USER_NAME'],
                                        ParamKeys::PROFILE_FILENAME => $row['PROFILE_FILENAME'],
                                        ParamKeys::AUTH_TOKEN => $row['AUTH_TOKEN'],
                                        ParamKeys::SESSION_ID => $sessionId,
                                    );
                                return $this->sendHttpResponseSuccess($response, $data);
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_INVALID_USER);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage());
        }
    }

    public function renewSessionId($request, $response, $args) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params)) {
                if ($params[ParamKeys::AUTH_TOKEN] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DEVICE_AUTH_REQUIRED);
                } else {
                    if ($this->db) {
                        //$statement = $this->db->prepare('SELECT * FROM ' . $this->getUserDetailsVW() . ' WHERE USER_ID = :userId AND AUTH_TOKEN = :authToken');
                        $statement = $this->db->prepare('SELECT ' . $this->getUserDetails() . '.* FROM ' . $this->getUserDetails() . ' JOIN ' . $this->getUserAuths() . '
                                                         ON ' . $this->getUserAuths() . '.USER_ID = ' . $this->getUserDetails() . '.USER_ID AND AUTH_TOKEN = :authToken
                                                         WHERE ' . $this->getUserDetails() . '.USER_ID = :userId');
                        $statement->bindParam(":userId", $args['id'], \PDO::PARAM_STR);
                        $statement->bindParam(":authToken", $params['AUTH_TOKEN'], \PDO::PARAM_STR);
                        $statement->execute();
                        $rows = $statement->fetch(\PDO::FETCH_ASSOC);
                        if ($rows) {
                            $sessionId = CommonUtils::build()->generateCode(32);
                            $this->db->beginTransaction();
                            $statementAuth = $this->db->prepare('UPDATE ' . $this->getUserAuths() . ' SET SESSION_ID = :sessionId WHERE USER_ID = :userId');
                            $statementAuth->bindParam(":userId", $args['id'], \PDO::PARAM_STR);
                            $statementAuth->bindParam(":sessionId", $sessionId, \PDO::PARAM_STR);
                            $statementAuth->execute();
                            $this->db->commit();

                            $data = array(
                                ParamKeys::USER_ID       => $args['id'],
                                ParamKeys::SESSION_ID    => $sessionId,
                                'SUPER_ADMIN'            => ($rows['SUPER_ADMIN'] == '1') ? '1' : '0',
                                'IS_SHIPPER'             => ($rows['IS_SHIPPER'] == '1') ? '1' : '0',
                                ParamKeys::QB_ID         => $rows['QB_ID'],
                                ParamKeys::QB_USER_LOGIN => $rows['QB_USER_LOGIN'],
                                ParamKeys::QB_PASSWORD   => $rows['QB_PASSWORD']
                            );
                            return $this->sendHttpResponseSuccess($response, $data);
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_CODE);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage());
        }
    }

    public function verifyAuth($request, $response) {
        try {
            $params = $request->getQueryParams();
            if (!empty($params)) {
                if ($params[ParamKeys::SESSION_ID] == "" && $params[ParamKeys::AUTH_TOKEN] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DEVICE_AUTH_REQUIRED);
                } else {
                    if ($this->db) {
                        $statement = $this->db->prepare('SELECT * FROM ' . $this->getUserDetailsVW() . ' WHERE SESSION_ID = :sessionId AND AUTH_TOKEN = :authToken');
                        $statement->bindParam(":sessionId", $params['SESSION_ID'], \PDO::PARAM_STR);
                        $statement->bindParam(":authToken", $params['AUTH_TOKEN'], \PDO::PARAM_STR);
                        $statement->execute();
                        $rows = $statement->fetch(\PDO::FETCH_ASSOC);
                        if ($rows) {
                            return $this->sendHttpResponseMessage($response,Messages::MSG_SUCCESS_AUTH_TOKEN);
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_CODE);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            return $this->sendHTTPResponseError($response, $e->getMessage());
        }
    }

    public function registerUser($request, $response) {
        try {
            $params = $request->getParsedBody();
            if (!empty($params) ) {
                if ($params[ParamKeys::USER_NAME] == "" || $params[ParamKeys::PASS_CODE] == "" || $params[ParamKeys::FULL_NAME] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
                        if (is_numeric($params[ParamKeys::USER_NAME])) {
                            $sqlCheck = 'SELECT * FROM ' . $this->getUserDetails() . ' WHERE MOBILE_NUMBER = :mobileNumber';
                            $column = "MOBILE_NUMBER";
                        } else {
                            if (!filter_var($params[ParamKeys::USER_NAME], FILTER_VALIDATE_EMAIL)) {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_EMAIL_FORMAT);
                            } else {
                                $sqlCheck = 'SELECT * FROM ' . $this->getUserDetails() . ' WHERE EMAIL_ADDRESS = :mobileNumber';
                                $column = "EMAIL_ADDRESS";
                            }
                        }
                        $statementCheck = $this->db->prepare($sqlCheck);
                        $statementCheck->bindParam(":mobileNumber", $params[ParamKeys::USER_NAME], \PDO::PARAM_STR);
                        $statementCheck->execute();
                        $rowCheck = $statementCheck->fetch(\PDO::FETCH_ASSOC);
                        if (!$rowCheck) {
                            $authToken = CommonUtils::build()->generateCode();
                            $this->db->beginTransaction();
                            $sqlDetails = 'INSERT INTO ' . $this->getUserDetails() . ' ('.$column.', FULL_NAME, DEVICE_TYPE, DEVICE_TOKEN) VALUES(:mobileEmail, :fullName, :device_type, :device_token)';
                            $statementDetails = $this->db->prepare($sqlDetails);
                            $statementDetails->bindParam(":mobileEmail", $params[ParamKeys::USER_NAME], \PDO::PARAM_STR);
                            $statementDetails->bindParam(":fullName", $params[ParamKeys::FULL_NAME], \PDO::PARAM_STR);
                            $statementDetails->bindParam(":device_type", $params[ParamKeys::DEVICE_TYPE], \PDO::PARAM_STR);
                            $statementDetails->bindParam(":device_token", $params[ParamKeys::DEVICE_TOKEN], \PDO::PARAM_STR);
                            $rowUserDetails = $statementDetails->execute();
                            $userId = $this->db->lastInsertId();
                            if ($rowUserDetails) {
                                $statementSecret = $this->db->prepare('INSERT INTO ' . $this->getUserSecrets() . ' (USER_ID, PASSCODE) VALUES(:userId, :passCode)');
                                $statementSecret->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                $statementSecret->bindParam(":passCode", $params[ParamKeys::PASS_CODE], \PDO::PARAM_STR);
                                $statementSecret->execute();
                                if ($userId > 0) {
                                    $statementAuth = $this->db->prepare('INSERT INTO ' . $this->getUserAuths() . ' (USER_ID, AUTH_TOKEN, SESSION_ID, DEVICE_TYPE) VALUES(:userId, :authToken, :sessionId, :deviceType)');
                                    $statementAuth->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                    $statementAuth->bindParam(":authToken", $authToken, \PDO::PARAM_STR);
                                    $statementAuth->bindParam(":sessionId", CommonUtils::build()->generateCode(32), \PDO::PARAM_STR);
                                    $statementAuth->bindParam(":deviceType", $params[ParamKeys::DEVICE_TYPE], \PDO::PARAM_STR);
                                    $statementAuth->execute();
                                    if ($userId > 0) {
                                        $sqlUserDetails = 'SELECT * FROM ' . $this->getUserDetailsVW() . ' WHERE USER_NAME = :mobileNumber';
                                        $statementUserDetails = $this->db->prepare($sqlUserDetails);
                                        $statementUserDetails->bindParam(":mobileNumber", $params['USER_NAME'], \PDO::PARAM_STR);
                                        $statementUserDetails->execute();
                                        $rowUserDetails = $statementUserDetails->fetch(\PDO::FETCH_ASSOC);
                                        if ($rowUserDetails) {
                                            $validationCode = CommonUtils::build()->generateValidationCode(6);
                                            $statementSecret = $this->db->prepare('INSERT INTO ' . $this->getValidationCode() . ' (USER_ID, VALIDATION_CODE) VALUES(:userId, :code)');
                                            $statementSecret->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                            $statementSecret->bindParam(":code", $validationCode, \PDO::PARAM_STR);
                                            $statementSecret->execute();
                                            $this->db->commit();
                                            $data = array(
                                                ParamKeys::USER_ID => $rowUserDetails['USER_ID'],
                                                ParamKeys::FULL_NAME => $rowUserDetails['FULL_NAME'],
                                                ParamKeys::USER_NAME => $rowUserDetails['USER_NAME'],
                                                ParamKeys::PROFILE_FILENAME => $rowUserDetails['PROFILE_FILENAME'],
                                                ParamKeys::AUTH_TOKEN => $rowUserDetails['AUTH_TOKEN'],
                                                ParamKeys::SESSION_ID => $rowUserDetails['SESSION_ID'],
                                                'VALIDATION_CODE' => $validationCode
                                            );
                                            return $this->sendHttpResponseSuccess($response, $data);
                                        }
                                    } else {
                                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_CREATE);
                                    }
                                }
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_USER_EXISTS);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function loginUser1($request, $response, $args) {
        try {
            $params = $request->getParsedBody();
            //print_r($args); exit();
            if (!empty($params) ) {
                if ($params[ParamKeys::USER_NAME] == "" || $params[ParamKeys::FULL_NAME] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
                        if (is_numeric($params[ParamKeys::USER_NAME])) {
                            $sqlCheck = 'SELECT * FROM ' . $this->getUserDetails() . ' WHERE MOBILE_NUMBER = :mobileNumber';
                            $column = "MOBILE_NUMBER";
                        } else {
                            if (!filter_var($params[ParamKeys::USER_NAME], FILTER_VALIDATE_EMAIL)) {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_EMAIL_FORMAT);
                            } else {
                                $sqlCheck = 'SELECT * FROM ' . $this->getUserDetails() . ' WHERE EMAIL_ADDRESS = :mobileNumber';
                                $column = "EMAIL_ADDRESS";
                            }
                        }
                        print_r($sqlCheck); exit();
                        $statementCheck = $this->db->prepare($sqlCheck);
                        $statementCheck->bindParam(":mobileNumber", $params[ParamKeys::USER_NAME], \PDO::PARAM_STR);
                        $statementCheck->execute();
                        $rowCheck = $statementCheck->fetch(\PDO::FETCH_ASSOC);
                        if (!$rowCheck) {
                            $authToken = CommonUtils::build()->generateCode();
                            $this->db->beginTransaction();
                            $sqlDetails = 'INSERT INTO ' . $this->getUserDetails() . ' ('.$column.', FULL_NAME, DEVICE_TYPE, DEVICE_TOKEN) VALUES(:mobileEmail, :fullName, :device_type, :device_token)';
                            $statementDetails = $this->db->prepare($sqlDetails);
                            $statementDetails->bindParam(":mobileEmail", $params[ParamKeys::USER_NAME], \PDO::PARAM_STR);
                            $statementDetails->bindParam(":fullName", $params[ParamKeys::FULL_NAME], \PDO::PARAM_STR);
                            $statementDetails->bindParam(":device_type", $params[ParamKeys::DEVICE_TYPE], \PDO::PARAM_STR);
                            $statementDetails->bindParam(":device_token", $params[ParamKeys::DEVICE_TOKEN], \PDO::PARAM_STR);
                            $rowUserDetails = $statementDetails->execute();
                            $userId = $this->db->lastInsertId();
                            if ($rowUserDetails) {
                                $statementSecret = $this->db->prepare('INSERT INTO ' . $this->getUserSecrets() . ' (USER_ID, PASSCODE) VALUES(:userId, :passCode)');
                                $statementSecret->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                $statementSecret->bindParam(":passCode", $params[ParamKeys::PASS_CODE], \PDO::PARAM_STR);
                                $statementSecret->execute();
                                if ($userId > 0) {
                                    $statementAuth = $this->db->prepare('INSERT INTO ' . $this->getUserAuths() . ' (USER_ID, AUTH_TOKEN, SESSION_ID, DEVICE_TYPE) VALUES(:userId, :authToken, :sessionId, :deviceType)');
                                    $statementAuth->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                    $statementAuth->bindParam(":authToken", $authToken, \PDO::PARAM_STR);
                                    $statementAuth->bindParam(":sessionId", CommonUtils::build()->generateCode(32), \PDO::PARAM_STR);
                                    $statementAuth->bindParam(":deviceType", $params[ParamKeys::DEVICE_TYPE], \PDO::PARAM_STR);
                                    $statementAuth->execute();
                                    if ($userId > 0) {
                                        $sqlUserDetails = 'SELECT * FROM ' . $this->getUserDetailsVW() . ' WHERE USER_NAME = :mobileNumber';
                                        $statementUserDetails = $this->db->prepare($sqlUserDetails);
                                        $statementUserDetails->bindParam(":mobileNumber", $params['USER_NAME'], \PDO::PARAM_STR);
                                        $statementUserDetails->execute();
                                        $rowUserDetails = $statementUserDetails->fetch(\PDO::FETCH_ASSOC);
                                        if ($rowUserDetails) {
                                            $validationCode = CommonUtils::build()->generateValidationCode(6);
                                            $statementSecret = $this->db->prepare('INSERT INTO ' . $this->getValidationCode() . ' (USER_ID, VALIDATION_CODE) VALUES(:userId, :code)');
                                            $statementSecret->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                            $statementSecret->bindParam(":code", $validationCode, \PDO::PARAM_STR);
                                            $statementSecret->execute();
                                            $this->db->commit();
                                            $this->db->beginTransaction();
                                            $sessionId = CommonUtils::build()->generateCode(32);
                                            $statementUpdate = $this->db->prepare('UPDATE ' . $this->getUserAuths() . ' SET SESSION_ID = :sessionId, UPDATE_DATE = :updatedDate WHERE USER_ID = :userId');
                                            $statementUpdate->bindParam(":userId", $row['USER_ID'], \PDO::PARAM_STR);
                                            $statementUpdate->bindParam(":sessionId", $sessionId, \PDO::PARAM_STR);
                                            $statementUpdate->bindParam(":updatedDate", CommonUtils::build()->getCurrentDateTime(), \PDO::PARAM_STR);
                                            $statementUpdate->execute();
                                            $this->db->commit();
                                            $data = array(
                                                ParamKeys::USER_ID => $rowUserDetails['USER_ID'],
                                                ParamKeys::FULL_NAME => $rowUserDetails['FULL_NAME'],
                                                ParamKeys::USER_NAME => $rowUserDetails['USER_NAME'],
                                                ParamKeys::PROFILE_FILENAME => $rowUserDetails['PROFILE_FILENAME'],
                                                ParamKeys::AUTH_TOKEN => $rowUserDetails['AUTH_TOKEN'],
                                                ParamKeys::SESSION_ID => $sessionId,
                                                'sessionId' => $sessionId
                                            );
                                            return $this->sendHttpResponseSuccess($response, $data);
                                        }
                                    } else {
                                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_CREATE);
                                    }
                                }
                            }
                        } else {
                            return $this->sendHTTPResponseError($response, Messages::MSG_ERR_USER_EXISTS);
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
    
    public function newloginapi($request, $response, $args) {
        try {
            $params = $request->getParsedBody();
           if (!empty($params) ) {
                if ($params[ParamKeys::USER_NAME] == "" || $params[ParamKeys::FULL_NAME] == "") {
                    return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                } else {
                    if ($this->db) {
                        if (is_numeric($params[ParamKeys::USER_NAME])) {
                            $user_name = substr($params[ParamKeys::USER_NAME], -10);
                            if(strlen($user_name) < 9 ){
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PHONE);
                            }else{
                                $user_name = '%'.$user_name;
                                $sqlCheck = 'SELECT * FROM ' . $this->getUserDetails() . ' WHERE MOBILE_NUMBER LIKE :mobileNumber';
                                $column = "MOBILE_NUMBER";
                                $PARAM_INT = true;
                            }
                        } else {
                            if (!filter_var($params[ParamKeys::USER_NAME], FILTER_VALIDATE_EMAIL)) {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_EMAIL_FORMAT);
                            } else {
                                $user_name = $params[ParamKeys::USER_NAME];
                                $sqlCheck = 'SELECT * FROM ' . $this->getUserDetails() . ' WHERE EMAIL_ADDRESS = :mobileNumber';
                                $column = "EMAIL_ADDRESS";
                                $PARAM_INT = false;
                            }
                        }
                        $statementCheck = $this->db->prepare($sqlCheck);
                        $statementCheck->bindParam(":mobileNumber", $user_name, \PDO::PARAM_STR);
                        $statementCheck->execute();
                        $rowCheck = $statementCheck->fetch(\PDO::FETCH_ASSOC);

                        if (!$rowCheck) {
							
							
							//----------------------------------------------------------------
							// delete previous device token from user table by rafiq
							//----------------------------------------------------------------
								if($params[ParamKeys::DEVICE_TOKEN]!="")
								{
									$queryDelDevice = $this->db->prepare('UPDATE ' . $this->getUserDetails() . ' SET DEVICE_TOKEN = NULL WHERE DEVICE_TOKEN = :device_token');
									$queryDelDevice->bindParam(":device_token", $params[ParamKeys::DEVICE_TOKEN], \PDO::PARAM_STR);								
									$rowDeletedDevice = $queryDelDevice->execute();
								}
								
								
							
                            $authToken = CommonUtils::build()->generateCode();
                            $this->db->beginTransaction();
                            $sqlDetails = 'INSERT INTO ' . $this->getUserDetails() . ' ('.$column.', FULL_NAME, DEVICE_TYPE, DEVICE_TOKEN) VALUES(:mobileEmail, :fullName, :device_type, :device_token)';
                            $statementDetails = $this->db->prepare($sqlDetails);
                            $statementDetails->bindParam(":mobileEmail", $params[ParamKeys::USER_NAME], \PDO::PARAM_STR);
                            $statementDetails->bindParam(":fullName", $params[ParamKeys::FULL_NAME], \PDO::PARAM_STR);
                            $statementDetails->bindParam(":device_type", $params[ParamKeys::DEVICE_TYPE], \PDO::PARAM_STR);
                            $statementDetails->bindParam(":device_token", $params[ParamKeys::DEVICE_TOKEN], \PDO::PARAM_STR);
                            $rowUserDetails = $statementDetails->execute();
                            $userId = $this->db->lastInsertId();
                            $this->db->commit();

                            $this->updateUserQBId($userId);

                            if ($rowUserDetails) {
                                $statementSecret = $this->db->prepare('INSERT INTO ' . $this->getUserSecrets() . ' (USER_ID) VALUES(:userId)');
                                $statementSecret->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                $statementSecret->execute();

                                if ($userId > 0) {
                                    $statementAuth = $this->db->prepare('INSERT INTO ' . $this->getUserAuths() . ' (USER_ID, AUTH_TOKEN, SESSION_ID, DEVICE_TYPE) VALUES(:userId, :authToken, :sessionId, :deviceType)');
                                    $statementAuth->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                    $statementAuth->bindParam(":authToken", $authToken, \PDO::PARAM_STR);
                                    $statementAuth->bindParam(":sessionId", CommonUtils::build()->generateCode(32), \PDO::PARAM_STR);
                                    $statementAuth->bindParam(":deviceType", $params[ParamKeys::DEVICE_TYPE], \PDO::PARAM_STR);
                                    $statementAuth->execute();

                                    if ($userId > 0) {
                                        $sqlUserDetails = 'SELECT * FROM ' . $this->getUserDetailsVW() . ' WHERE USER_NAME = :mobileNumber';
                                        $statementUserDetails = $this->db->prepare($sqlUserDetails);
                                        $statementUserDetails->bindParam(":mobileNumber", $params['USER_NAME'], \PDO::PARAM_STR);
                                        $statementUserDetails->execute();
                                        $rowUserDetails = $statementUserDetails->fetch(\PDO::FETCH_ASSOC);

                                        if ($rowUserDetails) {
                                            $this->db->beginTransaction();
                                            $validationCode = CommonUtils::build()->generateValidationCode(6);
                                            $statementSecret = $this->db->prepare('INSERT INTO ' . $this->getValidationCode() . ' (USER_ID, VALIDATION_CODE) VALUES(:userId, :code)');
                                            $statementSecret->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                            $statementSecret->bindParam(":code", $validationCode, \PDO::PARAM_STR);
                                            $statementSecret->execute();
                                            $this->db->commit();

                                            if(empty($rowUserDetails['AUTH_TOKEN']) && empty($rowUserDetails['SESSION_ID'])) {
                                                echo $sqlUserAuth = 'SELECT * FROM ptf_user_auth WHERE USER_ID = :userId';
                                                $sqlUserAuth = $this->db->prepare($sqlUserAuth);
                                                $sqlUserAuth->bindParam(":userId", $rowUserDetails['USER_ID'], \PDO::PARAM_STR);
                                                $sqlUserAuth->execute();
                                                $rowUserAuth = $sqlUserAuth->fetch(\PDO::FETCH_ASSOC);

                                                $rowUserDetails['AUTH_TOKEN'] = $rowUserAuth['AUTH_TOKEN'];
                                                $rowUserDetails['SESSION_ID'] = $rowUserAuth['SESSION_ID'];
                                            }

                                            $data = array(
                                                ParamKeys::USER_ID => $rowUserDetails['USER_ID'],
                                                ParamKeys::FULL_NAME => $rowUserDetails['FULL_NAME'],
                                                ParamKeys::USER_NAME => $rowUserDetails['USER_NAME'],
                                                ParamKeys::PROFILE_FILENAME => $rowUserDetails['PROFILE_FILENAME'],
                                                ParamKeys::AUTH_TOKEN => $rowUserDetails['AUTH_TOKEN'],
                                                ParamKeys::SESSION_ID => $rowUserDetails['SESSION_ID'],
                                                'VALIDATION_CODE' => $validationCode,
                                                'SUPER_ADMIN' => ($rowCheck['SUPER_ADMIN'] == '1') ? '1' : '0',
                                                'IS_SHIPPER' => ($rowCheck['IS_SHIPPER'] == '1') ? '1' : '0',
                                                ParamKeys::QB_ID => $rowUserDetails['QB_ID'],
                                                ParamKeys::QB_USER_LOGIN => $rowUserDetails['QB_USER_LOGIN'],
                                                ParamKeys::QB_PASSWORD => $rowUserDetails['QB_PASSWORD']
                                            );

                                            return $this->sendHttpResponseSuccess($response, $data);
                                        }
                                    } else {
                                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_CREATE);
                                    }
                                }
                            }
                        } else {
                            $userId = $rowCheck['USER_ID'];
                            $authToken = CommonUtils::build()->generateCode();
                            if($rowCheck['ACCOUNT_STATUS'] == "INACTIVE"){
                                $statementDetails = $this->db->prepare('UPDATE ' . $this->getUserDetails() . ' SET '.$column.' = :mobileEmail, FULL_NAME = :fullName, DEVICE_TYPE = :device_type, DEVICE_TOKEN = :device_token, ACCOUNT_STATUS = :account_status WHERE USER_ID = :userId');
								
                                $statementDetails->bindParam(":userId", $userId, \PDO::PARAM_STR);
                                $statementDetails->bindParam(":mobileEmail", $params[ParamKeys::USER_NAME], \PDO::PARAM_STR);
                                $statementDetails->bindParam(":fullName", $params[ParamKeys::FULL_NAME], \PDO::PARAM_STR);
                                $statementDetails->bindParam(":device_type", $params[ParamKeys::DEVICE_TYPE], \PDO::PARAM_STR);
                                $statementDetails->bindParam(":device_token", $params[ParamKeys::DEVICE_TOKEN], \PDO::PARAM_STR);
                                $account_status = "ACTIVE";
                                $statementDetails->bindParam(":account_status", $account_status, \PDO::PARAM_STR);
                                $rowUserDetails = $statementDetails->execute();
                            } else {
                                
								
								///// code by rafiq - for delete device token before update new user login device token
								if($params[ParamKeys::DEVICE_TOKEN]!="")
								{
									$queryDelDevice = $this->db->prepare('UPDATE ' . $this->getUserDetails() . ' SET DEVICE_TOKEN = NULL WHERE DEVICE_TOKEN = :device_token');
									$queryDelDevice->bindParam(":device_token", $params[ParamKeys::DEVICE_TOKEN], \PDO::PARAM_STR);								
									$rowDeletedDevice = $queryDelDevice->execute();
								}
								
								//die('UPDATE ' . $this->getUserDetails() . ' SET DEVICE_TOKEN = "" WHERE USER_ID = "'.$rowCheck[ParamKeys::USER_ID].'"');
								/////
								
								
								$statementDetails = $this->db->prepare('UPDATE ' . $this->getUserDetails() . ' SET '.$column.' = :mobileEmail, FULL_NAME = :fullName, DEVICE_TYPE = :device_type, DEVICE_TOKEN = :device_token WHERE USER_ID = :userId');
								
								//die('UPDATE ptf_user_details SET '.$column.' = "'.$rowCheck[ParamKeys::MOBILE_NUMBER].'", FULL_NAME = "'.$rowCheck[ParamKeys::MOBILE_NUMBER].'", DEVICE_TYPE = "'.$rowCheck[ParamKeys::DEVICE_TYPE].'", DEVICE_TOKEN = "'.$params[ParamKeys::DEVICE_TOKEN].'" WHERE USER_ID = "'.$rowCheck[ParamKeys::USER_ID].'"');
								
								
                                $statementDetails->bindParam(":userId", $rowCheck[ParamKeys::USER_ID], \PDO::PARAM_STR);
                                $statementDetails->bindParam(":mobileEmail", $rowCheck[ParamKeys::MOBILE_NUMBER], \PDO::PARAM_STR);
                                $statementDetails->bindParam(":fullName", $rowCheck[ParamKeys::FULL_NAME], \PDO::PARAM_STR);
                                $statementDetails->bindParam(":device_type", $rowCheck[ParamKeys::DEVICE_TYPE], \PDO::PARAM_STR);
								if($params[ParamKeys::DEVICE_TOKEN]!="")
								{
                                	$statementDetails->bindParam(":device_token", $params[ParamKeys::DEVICE_TOKEN], \PDO::PARAM_STR);
								}
								else
								{
									$statementDetails->bindParam(":device_token", $rowCheck[ParamKeys::DEVICE_TOKEN], \PDO::PARAM_STR);
								}
                                $rowUserDetails = $statementDetails->execute();
                            }

                            if(empty($rowCheck['QB_ID'])) {
                                $this->updateUserQBId($rowCheck['USER_ID']);
                            }

                            if ($userId > 0) {
                                $sqlUserDetails = 'SELECT * FROM ' . $this->getUserDetails() . ' WHERE USER_ID = :mobileNumber';
                                $statementUserDetails = $this->db->prepare($sqlUserDetails);
                                $statementUserDetails->bindParam(":mobileNumber", $userId, \PDO::PARAM_STR);
                                $statementUserDetails->execute();
                                $rowUserDetails = $statementUserDetails->fetch(\PDO::FETCH_ASSOC);

                                if(!$params[ParamKeys::SESSION]) {
                                    if (!$rowUserDetails['AUTH_TOKEN'] && !$rowUserDetails['SESSION_ID']) {
                                        $sqlUserAuth = 'SELECT * FROM ptf_user_auth WHERE USER_ID = :userId';
                                        $sqlUserAuth = $this->db->prepare($sqlUserAuth);
                                        $sqlUserAuth->bindParam(":userId", $rowUserDetails['USER_ID'], \PDO::PARAM_STR);
                                        $sqlUserAuth->execute();
                                        $rowUserAuth = $sqlUserAuth->fetch(\PDO::FETCH_ASSOC);

                                        $rowUserDetails['AUTH_TOKEN'] = $rowUserAuth['AUTH_TOKEN'];
                                        $rowUserDetails['SESSION_ID'] = $rowUserAuth['SESSION_ID'];
                                    }

                                    if ($rowUserDetails) {
                                        $data = array(
                                            ParamKeys::USER_ID => $rowUserDetails['USER_ID'],
                                            ParamKeys::FULL_NAME => $rowUserDetails['FULL_NAME'],
                                            ParamKeys::USER_NAME => $rowUserDetails['USER_NAME'],
                                            ParamKeys::PROFILE_FILENAME => $rowUserDetails['PROFILE_FILENAME'],
                                            ParamKeys::AUTH_TOKEN => $rowUserDetails['AUTH_TOKEN'],
                                            ParamKeys::SESSION_ID => $rowUserDetails['SESSION_ID'],
                                            'SUPER_ADMIN' => ($rowUserDetails['SUPER_ADMIN'] == '1') ? '1' : '0',
                                            'IS_SHIPPER' => ($rowUserDetails['IS_SHIPPER'] == '1') ? '1' : '0',
                                            ParamKeys::QB_ID => $rowUserDetails['QB_ID'],
                                            ParamKeys::QB_USER_LOGIN => $rowUserDetails['QB_USER_LOGIN'],
                                            ParamKeys::QB_PASSWORD => $rowUserDetails['QB_PASSWORD']
                                        );

                                        return $this->sendHttpResponseSuccess($response, $data);
                                    }
                                } else {
                                    $_SESSION['logged_in'] = array(
                                        ParamKeys::USER_ID => $rowUserDetails['USER_ID'],
                                        ParamKeys::FULL_NAME => $rowUserDetails['FULL_NAME'],
										ParamKeys::IS_SHIPPER => $rowUserDetails['IS_SHIPPER'],
										ParamKeys::SUPER_ADMIN => $rowUserDetails['SUPER_ADMIN'],
                                    );

                                    $data = array(
                                        ParamKeys::USER_ID => $rowUserDetails['USER_ID'],
                                        ParamKeys::FULL_NAME => $rowUserDetails['FULL_NAME'],
                                        ParamKeys::PROFILE_FILENAME => $rowUserDetails['PROFILE_FILENAME'],
                                        'SUPER_ADMIN' => ($rowUserDetails['SUPER_ADMIN'] == '1') ? '1' : '0',
                                        'IS_SHIPPER' => ($rowUserDetails['IS_SHIPPER'] == '1') ? '1' : '0',
                                        ParamKeys::QB_ID => $rowUserDetails['QB_ID'],
                                        ParamKeys::QB_USER_LOGIN => $rowUserDetails['QB_USER_LOGIN'],
                                        ParamKeys::QB_PASSWORD => $rowUserDetails['QB_PASSWORD']
                                    );

                                    return $this->sendHttpResponseSuccess($response, $data);
                                }
                            } else {
                                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_AUTH_CREATE);
                            }
                        }
                    } else {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_DB_CONNECTION);
                    }
                }
            } else {
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
            //$this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }

    public function updateUserQBId($user_id) {

        //Check if QB columns are empty then register user to QB and update QB columns
        $sqlQB = 'SELECT * FROM ' . $this->getUserDetails() . ' WHERE USER_ID = :userId';
        $sqlQBDetails = $this->db->prepare($sqlQB);
        $sqlQBDetails->bindParam(":userId", $user_id, \PDO::PARAM_STR);
        $sqlQBDetails->execute();
        $userQBDetails = $sqlQBDetails->fetch(\PDO::FETCH_ASSOC);

        if($userQBDetails) {
            if(empty($userQBDetails['QB_ID'])) {
                $this->qb_token = $this->_qbsession()->session->token;

                if(!empty($this->qb_token)) {
                    $qb_user_data = $this->_qbsignup($userQBDetails);

                    if(isset($qb_user_data->errors)) {
                        $qb_user_data_by_login = $this->_qbGetUserByLogin($userQBDetails['MOBILE_NUMBER']);

                        $qb_id = $qb_user_data_by_login->user->id;
                        $qb_login = $qb_user_data_by_login->user->login;

                    } else {
                        $qb_id = $qb_user_data->user->id;
                        $qb_login = $qb_user_data->user->login;
                    }

                    $this->db->beginTransaction();
                    $stm = $this->db->prepare('UPDATE ptf_user_details SET QB_ID = :qb_id, QB_USER_LOGIN = :qb_user_login, QB_PASSWORD = :qb_password WHERE USER_ID = :userId');
                    $stm->bindParam(":qb_id", $qb_id, \PDO::PARAM_STR);
                    $stm->bindParam(":qb_user_login", $qb_login, \PDO::PARAM_STR);
                    $stm->bindParam(":qb_password", $qb_login, \PDO::PARAM_STR);
                    $stm->bindParam(":userId", $user_id, \PDO::PARAM_STR);
                    $stm->execute();
                    $this->db->commit();
                }
            }
        }
    }
}