<?php

namespace Controllers;

use Constants\Messages;
use Constants\NotifyMessages;
use Constants\ParamKeys;
use Psr\Container\ContainerInterface;
use Validators\AuthValidatorController;
use Utils\PushNotifications as Notification;
use \RuntimeException;

class TaskNotifications  extends ControllerBase
{

    /** @var ContainerInterface */
    protected $container;
    protected $db;

    /**
     * Constructor
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function __construct(ContainerInterface $container)
    {
        // access container classes
        // eg $container->get('redis');
        $this->container = $container;
        $this->db = $this->container->primary;
    }

    /**
     * SampleTask command
     *
     * @param array $args
     * @return void
     */
    public function command($args)
    {
        // Access items in container
        $settings = $this->container->get('settings');

        // Throw if no arguments provided
        $count = 0;
        /*$s_date = date('Y-m-d H:i:00', strtotime("+23 hours"));
        $e_date = date('Y-m-d H:i:00', strtotime("+23 hours +15 minutes"));

        $tasks = $this->_get_task_by_time($s_date, $e_date);
        foreach ($tasks as $task) {
            $count++;
            // send notification
            $device_tokens[] = $this->getDeviceToken($task['ASSIGNED_ID']);
            $this->sendNotification($task['CREATOR_ID'], $task['ASSIGNED_ID'],
                $task['TASK_ID'], 'task', 'TASK_REMEMBER', $device_tokens, 'a day.');
        }

        $s_date = date('Y-m-d H:i:00', strtotime("+3 hours"));
        $e_date = date('Y-m-d H:i:00', strtotime("+3 hours +15 minutes"));
        $tasks = $this->_get_task_by_time($s_date, $e_date);
        foreach ($tasks as $task) {
            $count++;
            // send notification
            $device_tokens[] = $this->getDeviceToken($task['ASSIGNED_ID']);
            $this->sendNotification($task['CREATOR_ID'], $task['ASSIGNED_ID'],
                $task['TASK_ID'], 'task', 'TASK_REMEMBER', $device_tokens, '3 hours.');
        }

        $s_date = date('Y-m-d H:i:00', strtotime("+1 hours"));
        $e_date = date('Y-m-d H:i:00', strtotime("+1 hours +15 minutes"));

        $tasks = $this->_get_task_by_time($s_date, $e_date);
        foreach ($tasks as $task) {
            $count++;
            // send notification
            $device_tokens[] = $this->getDeviceToken($task['ASSIGNED_ID']);
            $this->sendNotification($task['CREATOR_ID'], $task['ASSIGNED_ID'],
                $task['TASK_ID'], 'task', 'TASK_REMEMBER', $device_tokens, 'an hour.');
        }*/

        $s_date = date('Y-m-d H:i:00', strtotime("+15 minutes"));
        $e_date = date('Y-m-d H:i:00', strtotime("+20 minutes"));
        
        $current_time = date('Y-m-d H:i:00', time());
        $currentt_time = date('Y-m-d H:i:00', time() + 15*60);
        
        $tasks = $this->_get_task_by_time_15_min($s_date, $e_date);
        foreach ($tasks as $task) {
            $count++;
            // send notification
            $device_tokens[] = $this->getDeviceToken($task['ASSIGNED_ID']);
            $this->sendNotification($task['CREATOR_ID'], $task['ASSIGNED_ID'],
                $task['TASK_ID'], 'task', 'TASK_REMEMBER', $device_tokens, '15 minutes.');
        }
        echo $current_time . ' ' . $currentt_time . ' ' . "Total $count Notification Pushed";
    }

    public function getDeviceToken($userId) {
        $taskSql = "select DEVICE_TOKEN as device_token from ptf_user_details where USER_ID = :userId";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":userId", $userId);
        $tasksStatement->execute();
        $row = $tasksStatement->fetch(\PDO::FETCH_ASSOC);
        return $row['device_token'];
    }

    private function _get_task_by_time_15_min($s_date, $e_date)
    {
        /*//$taskSql = "select * from cor_project_tasks where DUE_DATE_DT >= :s_date AND DUE_DATE_DT <= :e_date AND PROJECT_ID is not null";
        $taskSql = "SELECT * FROM cor_project_tasks WHERE (DUE_DATE-UNIX_TIMESTAMP()) BETWEEN (13*60) AND (15*60) AND PROJECT_ID IS NOT NULL ORDER BY TASK_ID DESC";*/
        
        $current_time = date('Y-m-d H:i:00', time());
        
        $taskSql = "SELECT * FROM cor_project_tasks WHERE TIMESTAMPDIFF(MINUTE, '$current_time', DUE_DATE_DT) BETWEEN 13 AND 15 ORDER BY TASK_ID DESC";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":s_date", $s_date);
        $tasksStatement->bindParam(":e_date", $e_date);
        $tasksStatement->execute();

        return $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
?>