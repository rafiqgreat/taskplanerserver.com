<?php

namespace Controllers;

use Constants\Messages;
use Constants\NotifyMessages;
use Constants\ParamKeys;
use Psr\Container\ContainerInterface;
use Validators\AuthValidatorController;
use Utils\PushNotifications as Notification;
use \RuntimeException;

class OverDueTaskNotifications  extends ControllerBase
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

        $tasks = $this->get_over_due_tasks_simple();
        if(!empty($tasks)) {
            foreach ($tasks as $task) {
                $device_tokens = array();

                $count++;
                // send notification
                $device_tokens[] = $this->getDeviceToken($task['ASSIGNED_ID']);

                $object_title = $this->human_readable_time($task['DUE_DATE'], $this->getCurrentTimestamp());

                $this->sendNotification($task['CREATOR_ID'], $task['ASSIGNED_ID'], $task['TASK_ID'], 'task', 'TASK_OVERDUE', $device_tokens, $object_title);
            }
        }

        $tasks = $this->get_over_due_tasks_interval();
        if(!empty($tasks)) {
            foreach ($tasks as $task) {
                $device_tokens = array();

                $count++;
                // send notification
                $device_tokens[] = $this->getDeviceToken($task['ASSIGNED_ID']);

                $object_title = $this->human_readable_time($task['DUE_DATE'], $this->getCurrentTimestamp());

                $this->sendNotification($task['CREATOR_ID'], $task['ASSIGNED_ID'], $task['TASK_ID'], 'task', 'TASK_OVERDUE', $device_tokens, $object_title);
            }
        }


        echo "Total $count Over Due Tasks Notifications Pushed";
    }

    public function getDeviceToken($userId) {
        $taskSql = "select DEVICE_TOKEN as device_token from ptf_user_details where USER_ID = :userId";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":userId", $userId);
        $tasksStatement->execute();
        $row = $tasksStatement->fetch(\PDO::FETCH_ASSOC);
        return $row['device_token'];
    }

    private function get_over_due_tasks_simple()
    {
        $taskSql = "SELECT * FROM cor_project_tasks WHERE DUE_DATE < UNIX_TIMESTAMP() AND DUE_DATE > 0 AND TASK_STATUS != 'COMPLETED' AND TASK_STATUS != 'CLOSED' AND REPEAT_INTERVAL = '' ORDER BY TASK_ID DESC";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->execute();

        return $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function get_over_due_tasks_interval()
    {
        //$taskSql = "SELECT * FROM cor_project_tasks WHERE DUE_DATE < UNIX_TIMESTAMP() AND DUE_DATE > 0 AND TASK_STATUS != 'COMPLETED' AND TASK_STATUS != 'CLOSED' AND REPEAT_INTERVAL != '' ORDER BY TASK_ID DESC";
        $taskSql = "SELECT * FROM cor_project_tasks WHERE DUE_DATE < UNIX_TIMESTAMP() AND DUE_DATE > 0 AND TASK_STATUS != 'COMPLETED' AND TASK_STATUS != 'CLOSED' AND REPEAT_INTERVAL != '' ORDER BY TASK_ID DESC";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->execute();

        return $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function human_readable_time($due_date, $current_date)
    {
        $due_date = $this->convertTimestampToDate($due_date);
        $current_date = $this->convertTimestampToDate($current_date);

        $date1 = new \DateTime($current_date);
        $date2 = $date1->diff(new \DateTime($due_date));

        if ($date2->y > 0)
            $response = $this->time_output($date2->y, 'year');
        else if ($date2->m > 0)
            $response = $this->time_output($date2->m, 'month');
        else if ($date2->d > 0)
            $response = $this->time_output($date2->d, 'day');
        else if ($date2->h > 0)
            $response = $this->time_output($date2->h, 'hour');
        else if ($date2->i > 0)
            $response = $this->time_output($date2->i, 'minute');
        else if ($date2->s > 0)
            $response = $this->time_output($date2->s, 'second');

        return $response;
    }

    private function time_output($int, $str)
    {
        $text = $int . ' ';
        $text .= ($int > 1) ? $str . 's' : $str;
        $text .= ' ago';

        return $text;
    }
}
?>