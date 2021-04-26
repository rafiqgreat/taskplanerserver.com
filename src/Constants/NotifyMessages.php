<?php
/**
 * Created by PhpStorm.
 * User: asif
 * Date: 23/02/2018
 * Time: 1:30 PM
 */

namespace Constants;


class NotifyMessages {

	public function getNotificationText($type)
	{
		switch ($type) {
			case 'NEW_TASK_MEMBER_ADDED':
				return ":subject added you in task :object";
			case 'NEW_PROJECT_MEMBER_ADDED':
    			return ":subject added you in project :object";
			case 'NEW_MESSAGE_SENDED':
    			return ":subject send you a message :object";
		    case 'TASK_REMEMBER':
				return ":subject - :object left";
            case 'TASK_OVERDUE':
                return ":subject - (:object)";
            case 'STATUS_CHANGED':
                return ":subject status changed to :object";
            case 'NEW_SHIPMENT_ADDED':
                return ":subject has added new shipment";
            case 'SHIPMENT_UPDATED':
                return ":subject has updated shipment";
            case 'SHIPMENT_STATUS_CHANGED':
                return ":subject status has changed to :object";
            case 'SHIPMENT_APPROVED':
                return ":subject has been approved";
        break;
			default:
				return false;
		}
	}
}