<?php
/**
 * Created by IntelliJ IDEA.
 * User: nayab
 * Date: 06-Nov-17
 * Time: 5:38 PM
 */

namespace Controllers;

use Constants\Messages;
use Constants\ParamKeys;
use Psr\Container\ContainerInterface;
use Validators\AuthValidatorController;

class AssignedTasksController extends ControllerBase
{
    protected $container;
    protected $db;

    // constructor receives container instance
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->db = $this->container->primary;
    }

    public function getAssignProjectsTasks($request, $response)
    {
        try {
            $params = $request->getQueryParams();
            if (!empty($params) ) {
                //if (AuthValidatorController::validateAuth($this->db, $params[ParamKeys::AUTH_TOKEN], $params[ParamKeys::SESSION_ID])) {
                    if ($params[ParamKeys::USER_ID] == "") {
                        return $this->sendHTTPResponseError($response, Messages::MSG_ERR_PARAMS_VALUE_MISSING);
                    } else {
                        if ($this->db) {
                            $taskSql = "select PROJECT_ID, PROJECT_NAME, PROJECT_STATUS from ". $this->getAssignedProjectsVW() . " where ASSIGNED_ID = :projectId AND PROJECT_STATUS = 'OPEN'";
                            $tasksStatement = $this->db->prepare($taskSql);
                            $tasksStatement->bindParam(":assignedId", $params[ParamKeys::USER_ID], \PDO::PARAM_STR);
                            $tasksStatement->execute();
                            $results = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
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
                return $this->sendHTTPResponseError($response, Messages::MSG_ERR_HTTP_PARAMS);
            }
        } catch (\PDOException $e) {
//            $this->db->rollBack();
            return $this->sendHTTPResponseError($response, $e->getMessage()." | Line Number: ".$e->getLine());
        }
    }
}