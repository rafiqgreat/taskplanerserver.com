<?php
/**
 * Created by IntelliJ IDEA.
 * User: nayab
 * Date: 23-Sep-17
 * Time: 3:51 AM
 */

namespace Validators;

class AuthValidatorController extends \Controllers\ControllerBase
{

    public static function validateAuth($db, $authToken, $sessionId)
    {
        if ($authToken != "" && $sessionId != "") {
            if ($db) {
                $statement = $db->prepare('select * from ptf_user_details_vw where AUTH_TOKEN = :authToken and SESSION_ID = :sessionId');
                $statement->bindParam(":authToken", $authToken, \PDO::PARAM_STR);
                $statement->bindParam(":sessionId", $sessionId, \PDO::PARAM_STR);
                $statement->execute();
                $authRow = $statement->fetch(\PDO::FETCH_ASSOC);
                if ($authRow) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}