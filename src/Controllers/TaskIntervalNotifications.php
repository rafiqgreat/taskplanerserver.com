<?php

namespace Controllers;

use Constants\Messages;
use Constants\NotifyMessages;
use Constants\ParamKeys;
use Psr\Container\ContainerInterface;
use Validators\AuthValidatorController;
use Utils\PushNotifications as Notification;
use \RuntimeException;

class TaskIntervalNotifications  extends ControllerBase
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
/*
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
                //$this->_update_task_duedate($task['TASK_ID']);
                $this->sendNotification($task['CREATOR_ID'], $task['ASSIGNED_ID'],
                    $task['TASK_ID'], 'task', 'TASK_REMEMBER', $device_tokens, '30 minutes.');
            }
        }
*/
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
        
        $s1 = (23*60*60) + (55*60);
        $s2 = (24*60*60);

        $taskSql = "SELECT * FROM cor_project_tasks WHERE (DUE_DATE - UNIX_TIMESTAMP()) BETWEEN :s AND :e AND TASK_STATUS != 'CLOSED' AND TASK_STATUS != 'COMPLETED' AND FIND_IN_SET(:currentDay, REPEAT_INTERVAL) ORDER BY TASK_ID DESC";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":currentDay", $weekDays[$currentDay]);
        $tasksStatement->bindParam(":s", $s1);
        $tasksStatement->bindParam(":e", $s2);
        $tasksStatement->execute();

        return $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function _get_task_by_time_3_hour()
    {
        $weekDays = array('Monday' => '0', 'Tuesday' => 1, 'Wednesday' => 2, 'Thursday' => 3, 'Friday' => 4, 'Saturday' => 5, 'Sunday' => 6);
        $currentDay = date('l');
        
        $s1 = (2*60*60) + (55*60);
        $s2 = (3*60*60);

        $taskSql = "SELECT * FROM cor_project_tasks WHERE (DUE_DATE - UNIX_TIMESTAMP()) BETWEEN :s AND :e AND TASK_STATUS != 'CLOSED' AND TASK_STATUS != 'COMPLETED' AND FIND_IN_SET(:currentDay, REPEAT_INTERVAL) ORDER BY TASK_ID DESC";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":currentDay", $weekDays[$currentDay]);
        $tasksStatement->bindParam(":s", $s1);
        $tasksStatement->bindParam(":e", $s2);
        $tasksStatement->execute();

        return $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function _get_task_by_time_1_hour()
    {
        $weekDays = array('Monday' => '0', 'Tuesday' => 1, 'Wednesday' => 2, 'Thursday' => 3, 'Friday' => 4, 'Saturday' => 5, 'Sunday' => 6);
        $currentDay = date('l');
        
        $s1 = (55*60);
        $s2 = (60*60);

        $taskSql = "SELECT * FROM cor_project_tasks WHERE (DUE_DATE - UNIX_TIMESTAMP()) BETWEEN :s AND :e AND TASK_STATUS != 'CLOSED' AND TASK_STATUS != 'COMPLETED' AND FIND_IN_SET(:currentDay, REPEAT_INTERVAL) ORDER BY TASK_ID DESC";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":currentDay", $weekDays[$currentDay]);
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
        $s2 = (37*60);

        $taskSql = "SELECT * FROM cor_project_tasks WHERE (DUE_DATE - UNIX_TIMESTAMP()) BETWEEN :s AND :e AND TASK_STATUS != 'CLOSED' AND TASK_STATUS != 'COMPLETED' AND FIND_IN_SET(:currentDay, REPEAT_INTERVAL) ORDER BY TASK_ID DESC";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":currentDay", $weekDays[$currentDay]);
        $tasksStatement->bindParam(":s", $s1);
        $tasksStatement->bindParam(":e", $s2);
        $tasksStatement->execute();

        return $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function _update_task_duedate($task_id) {
        $status = 'OPEN';
        $days = array('0' => 'Monday', '1' => 'Tuesday', '2' => 'Wednesday', '3' => 'Thursday', '4' => 'Friday', '5' => 'Saturday', '6' => 'Sunday');
        $today = date('l');

        $taskSql = "select * from ". $this->getTasks() . " where TASK_ID = :task_id";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":task_id", $task_id);
        $tasksStatement->execute();

        $task = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

        if($task) {
            $taskInterval = explode(',',$task[0]['REPEAT_INTERVAL']);
            $dueDate = date('Y-m-d h:i:s', strtotime($task[0]['DUE_DATE_DT']));

            $taskTime = explode(' ', $dueDate);

            sort($taskInterval);
            $dayIndex = array_search($today, $days);

            if(strlen($task[0]['REPEAT_INTERVAL']) > 1) {
                if (in_array($dayIndex, $taskInterval)) {
                    $currentIndex = array_search($dayIndex, $taskInterval);
                    $countRepeatInterval = count($taskInterval);
                    $lastIndex = $countRepeatInterval - 1;

                    if ($currentIndex == $lastIndex) {
                        $nextIndex = '0';
                    } else {
                        $nextIndex = $currentIndex + 1;
                    }

                    $nextDay = $days[$taskInterval[$nextIndex]];
                    $nextDueDate = date('Y-m-d', strtotime('next ' . $nextDay)) . ' ' . $taskTime[1];
                    $nextDueDateTs = $this->convertToTimestamp($nextDueDate);

                    $tasksStatement = $this->db->prepare('UPDATE ' . $this->getTasks() . ' set DUE_DATE_DT = :dueDateDt, DUE_DATE = :dueDate, TASK_STATUS = :status where TASK_ID = :taskId');
                    $tasksStatement->bindParam(":dueDate", $nextDueDateTs, \PDO::PARAM_STR);
                    $tasksStatement->bindParam(":dueDateDt", $nextDueDate, \PDO::PARAM_STR);
                    $tasksStatement->bindParam(":status", $status, \PDO::PARAM_STR);
                    $tasksStatement->bindParam(":taskId", $task_id, \PDO::PARAM_STR);

                    $tasksStatement->execute();
                }
            } else {
                $nextDueDate = date('Y-m-d h:i:s', strtotime('next ' . $days[$task[0]['REPEAT_INTERVAL']]));
                $nextDueDateTs = $this->convertToTimestamp($nextDueDate);

                $tasksStatement = $this->db->prepare('UPDATE ' . $this->getTasks() . ' set DUE_DATE = :dueDate, DUE_DATE_DT = :dueDateDt, TASK_STATUS = :status where TASK_ID = :taskId');
                $tasksStatement->bindParam(":dueDate", $nextDueDateTs, \PDO::PARAM_STR);
                $tasksStatement->bindParam(":dueDateDt", $nextDueDate, \PDO::PARAM_STR);
                $tasksStatement->bindParam(":status", $status, \PDO::PARAM_STR);
                $tasksStatement->bindParam(":taskId", $task_id, \PDO::PARAM_STR);

                $tasksStatement->execute();
            }
        }
    }
}
?>