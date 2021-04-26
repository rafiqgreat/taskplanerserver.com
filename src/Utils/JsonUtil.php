<?php
/**
 * Created by IntelliJ IDEA.
 * User: nayab
 * Date: 22-Sep-17
 * Time: 8:45 PM
 */
namespace Utils;

use Constants\JsonKeys;
use Constants\DBTables;

class JsonUtil extends DBTables
{
    private static $instance;
    private function __construct(){}
    static public function build()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function convertToJson($data = array()) {
        return json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE );
    }

    function getJsonObject($status, $message = '', $data = Array()) {
        return $this->convertToJson(array(JsonKeys::STATUS => $status, JsonKeys::MESSAGE => $message,  JsonKeys::DATA => $this->convertToJson($data)));
    }
}
?>