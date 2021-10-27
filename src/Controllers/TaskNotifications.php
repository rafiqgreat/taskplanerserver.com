<?php

namespace Controllers;

use Constants\Messages;
use Constants\NotifyMessages;
use Constants\ParamKeys;
use Psr\Container\ContainerInterface;
use Validators\AuthValidatorController;
use Utils\PushNotifications as Notification;
use \RuntimeException;

class TaskNotifications extends ControllerBase
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

        $tasks = $this->_get_task_by_time_24_hour();
        if(!empty($tasks)) {
            foreach ($tasks as $task) {
                $count++;
                // send notification
                $device_tokens[] = $this->getDeviceToken($task['ASSIGNED_ID']);
                $this->sendNotification($task['CREATOR_ID'], $task['ASSIGNED_ID'],
                    $task['TASK_ID'], 'task', 'TASK_REMEMBER', $device_tokens, '24 hours.');
            }
        }

        $tasks = $this->_get_task_by_time_3_hour();
        if(!empty($tasks)) {
            foreach ($tasks as $task) {
                $count++;
                // send notification
                $device_tokens[] = $this->getDeviceToken($task['ASSIGNED_ID']);
                $this->sendNotification($task['CREATOR_ID'], $task['ASSIGNED_ID'],
                    $task['TASK_ID'], 'task', 'TASK_REMEMBER', $device_tokens, '3 hours.');
            }
        }

        $tasks = $this->_get_task_by_time_1_hour();
        if(!empty($tasks)) {
            foreach ($tasks as $task) {
                $count++;
                // send notification
                $device_tokens[] = $this->getDeviceToken($task['ASSIGNED_ID']);
                $this->sendNotification($task['CREATOR_ID'], $task['ASSIGNED_ID'],
                    $task['TASK_ID'], 'task', 'TASK_REMEMBER', $device_tokens, '1 hour.');
            }
        }

        $tasks = $this->_get_task_by_time_30_min();
        if(!empty($tasks)) {
            foreach ($tasks as $task) {
                $count++;
                // send notification
                $device_tokens[] = $this->getDeviceToken($task['ASSIGNED_ID']);
                $this->sendNotification($task['CREATOR_ID'], $task['ASSIGNED_ID'],
                    $task['TASK_ID'], 'task', 'TASK_REMEMBER', $device_tokens, '30 minutes.');
            }
        }

		 $tasksStat = $this->db->prepare("UPDATE `cor_project_tasks` SET DUE_DATE_DT = NULL WHERE DUE_DATE_DT LIKE '0000-00-00 00:00:00' OR DUE_DATE_DT LIKE '1969%' OR DUE_DATE_DT LIKE '1970%'");
          $tasksStat->execute();
        echo "Total $count Interval Notification Pushed";
    }

    public function getDeviceToken($userId) {
        $taskSql = "select DEVICE_TOKEN as device_token from ptf_user_details where USER_ID = :userId";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":userId", $userId);
        $tasksStatement->execute();
        $row = $tasksStatement->fetch(\PDO::FETCH_ASSOC);
        return $row['device_token'];
    }

    private function _get_task_by_time_24_hour()
    {
        $weekDays = array('Monday' => '0', 'Tuesday' => 1, 'Wednesday' => 2, 'Thursday' => 3, 'Friday' => 4, 'Saturday' => 5, 'Sunday' => 6);
        $currentDay = date('l');

        $s1 = (23*60*60) + (52*60);
        $s2 = (24*60*60) + (8*60);

        $taskSql = "SELECT * FROM cor_project_tasks WHERE (DUE_DATE - UNIX_TIMESTAMP()) BETWEEN :s AND :e AND TASK_STATUS != 'CLOSED' AND TASK_STATUS != 'COMPLETED' AND REPEAT_INTERVAL = '' ORDER BY TASK_ID DESC";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":s", $s1);
        $tasksStatement->bindParam(":e", $s2);
        $tasksStatement->execute();

        return $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function _get_task_by_time_3_hour()
    {
        $weekDays = array('Monday' => '0', 'Tuesday' => 1, 'Wednesday' => 2, 'Thursday' => 3, 'Friday' => 4, 'Saturday' => 5, 'Sunday' => 6);
        $currentDay = date('l');

        $s1 = (2*60*60) + (52*60);
        $s2 = (3*60*60) + (8*60);

        $taskSql = "SELECT * FROM cor_project_tasks WHERE (DUE_DATE - UNIX_TIMESTAMP()) BETWEEN :s AND :e AND TASK_STATUS != 'CLOSED' AND TASK_STATUS != 'COMPLETED' AND REPEAT_INTERVAL = '' ORDER BY TASK_ID DESC";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":s", $s1);
        $tasksStatement->bindParam(":e", $s2);
        $tasksStatement->execute();

        return $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function _get_task_by_time_1_hour()
    {
        $weekDays = array('Monday' => '0', 'Tuesday' => 1, 'Wednesday' => 2, 'Thursday' => 3, 'Friday' => 4, 'Saturday' => 5, 'Sunday' => 6);
        $currentDay = date('l');

        $s1 = (52*60);
        $s2 = (60*60) + (8*60);

        $taskSql = "SELECT * FROM cor_project_tasks WHERE (DUE_DATE - UNIX_TIMESTAMP()) BETWEEN :s AND :e AND TASK_STATUS != 'CLOSED' AND TASK_STATUS != 'COMPLETED' AND REPEAT_INTERVAL = '' ORDER BY TASK_ID DESC";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":s", $s1);
        $tasksStatement->bindParam(":e", $s2);
        $tasksStatement->execute();

        return $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function _get_task_by_time_30_min()
    {
        $weekDays = array('Monday' => '0', 'Tuesday' => 1, 'Wednesday' => 2, 'Thursday' => 3, 'Friday' => 4, 'Saturday' => 5, 'Sunday' => 6);
        $currentDay = date('l');

        $s1 = (22*60);
        $s2 = (38*60);

        $taskSql = "SELECT * FROM cor_project_tasks WHERE (DUE_DATE - UNIX_TIMESTAMP()) BETWEEN :s AND :e AND TASK_STATUS != 'CLOSED' AND TASK_STATUS != 'COMPLETED' AND REPEAT_INTERVAL = '' ORDER BY TASK_ID DESC";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":s", $s1);
        $tasksStatement->bindParam(":e", $s2);
        $tasksStatement->execute();

        return $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
    }

}
?>