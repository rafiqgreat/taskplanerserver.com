<?php

namespace Controllers;

use Constants\Messages;
use Constants\NotifyMessages;
use Constants\ParamKeys;
use Psr\Container\ContainerInterface;
use Validators\AuthValidatorController;
use Utils\PushNotifications as Notification;
use \RuntimeException;

class ChangeStatusOfRepeatedTasks  extends ControllerBase
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
		$today_date = date('Y-m-d');
		
        //$currentTimeStamp = $this->getCurrentTimestamp();

        $taskSql = "SELECT * FROM ". $this->getTasks() . " WHERE TASK_STATUS = 'COMPLETED' AND REPEAT_INTERVAL != '' AND DATE(DUE_DATE_DT) <= DATE('$today_date') ORDER BY DUE_DATE DESC";
        //$taskSql = "SELECT * FROM ". $this->getTasks() . " WHERE REPEAT_INTERVAL != '' AND DUE_DATE < $currentTimeStamp ORDER BY DUE_DATE DESC";
		echo ($taskSql).'<br />';
		//die($taskSql);
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->execute();
        $tasks = $tasksStatement->fetchAll(\PDO::FETCH_ASSOC);

        $taskIds = array();
		$tsk = 0; // rafiq
		$tsk_m = 0; // rafiq
        $count = 0;
        foreach($tasks as $task):

            $taskIds[] = $task['TASK_ID'];

            if($task['REPEAT_INTERVAL'] != 7) {
				$tsk++;
                $this->_update_task_duedate($task['TASK_ID']);
            } else {
				$tsk_m++;
                $this->_update_task_duedate_month($task['TASK_ID']);
            }

            $count++;
        endforeach;

        $this->logCron('ChangeStatusOfRepeatedTasks', $count, $taskIds);

        echo "Total $count Task(s) Status Changed[".$tsk.",".$tsk_m."]";
        die();
    }

    private function _update_task_duedate($task_id) {
        $status = 'OPEN';
        $days = array('0' => 'monday', '1' => 'tuesday', '2' => 'wednesday', '3' => 'thursday', '4' => 'friday', '5' => 'saturday', '6' => 'sunday');
        $today = date('l'); // full name of day as Monday, Tuesday ...
        $dayIndex = array_search(strtolower($today), $days); // having todays index or repeat day today index

        $taskSql = "SELECT * FROM ". $this->getTasks() . " WHERE TASK_ID = :task_id ";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":task_id", $task_id);
        $tasksStatement->execute();
        $task = $tasksStatement->fetch(\PDO::FETCH_ASSOC);

        if($task) {
			
            //$dueDate = $this->convertTimestampToDate($task['DUE_DATE']);
            $extractTime = explode(' ', $task['DUE_DATE_DT']);
            $interval = explode(',', $task['REPEAT_INTERVAL']); // task all intervals in array 

            sort($interval);

            $index = array_search($dayIndex, $interval); //today index  is in database repeated indexes

            if($index !== false && $index < count($interval)-1) {  // index is less than total db indexes
                $next = $interval[$index + 1];  // next is next repeated day in db
            } else {
                $next = $interval[0]; // next is first repeated day in db			
				
            }

            $nextDueDate = date('Y-m-d', strtotime('next ' . $days[$next])) . ' ' . $extractTime[1];
            //$nextDueDateTs = $this->convertToTimestamp($nextDueDate);
			//$date = '2021-02-20 22:00:00';
			date_default_timezone_set("Asia/Karachi");
			//echo "The time is " . date("h:i:sa");
			//$d = \DateTime::createFromFormat('Y-m-d H:i:s',$nextDueDate,new DateTimeZone('Asia/Karachi'));
			//$nextDueDateTs = $d->getTimestamp();
            $params = array(
                'DUE_DATE'      => strtotime($nextDueDate),
                'DUE_DATE_DT'   => $nextDueDate,
                'STATUS'        => 'OPEN',
                'TASK_ID'       => $task['TASK_ID']
            );
			print_r($params);
			echo '<=================';
            $this->_update_task($params);
        }
    }


    private function _update_task_duedate_month($task_id) {

        $status = 'OPEN';

        $taskSql = "SELECT * FROM ". $this->getTasks() . " WHERE TASK_ID = :task_id";
        $tasksStatement = $this->db->prepare($taskSql);
        $tasksStatement->bindParam(":task_id", $task_id);
        $tasksStatement->execute();
        $task = $tasksStatement->fetch(\PDO::FETCH_ASSOC);

        if($task) {
            $extractTime = explode(' ', $task['DUE_DATE_DT']);

            $nextDueDate = date('Y-m-d', strtotime('+1 month')) . ' ' . $extractTime[1];
            $nextDueDateTs = $this->convertToTimestamp($nextDueDate);
			date_default_timezone_set("Asia/Karachi");
            $params = array(
                //'DUE_DATE'      => $nextDueDateTs,
				'DUE_DATE'      => strtotime($nextDueDate),
                'DUE_DATE_DT'   => $nextDueDate,
                'STATUS'        => 'OPEN',
                'TASK_ID'       => $task['TASK_ID']
            );

            $this->_update_task($params);
        }
    }

    private function _update_task($params) {
        $this->db->beginTransaction();

        $tasksStatement = $this->db->prepare('UPDATE ' . $this->getTasks() . ' set DUE_DATE_DT = :dueDateDt, DUE_DATE = :dueDate, TASK_STATUS = :status, UPDATED_DATE = ' . $this->getCurrentTimestamp() . ' WHERE TASK_ID = :taskId');
        $tasksStatement->bindParam(":dueDate", $params['DUE_DATE'], \PDO::PARAM_STR);
        $tasksStatement->bindParam(":dueDateDt", $params['DUE_DATE_DT'], \PDO::PARAM_STR);
        $tasksStatement->bindParam(":status", $params['STATUS'], \PDO::PARAM_STR);
        $tasksStatement->bindParam(":taskId", $params['TASK_ID'], \PDO::PARAM_STR);
        $tasksStatement->execute();

        $this->db->commit();
    }
}
?>