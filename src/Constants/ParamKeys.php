<?php
/**
 * Created by IntelliJ IDEA.
 * User: nayab
 * Date: 22-Sep-17
 * Time: 10:38 PM
 */

namespace Constants;

class ParamKeys
{
    const SESSION_ID = "SESSION_ID";
    const AUTH_TOKEN = "AUTH_TOKEN";
    const DEVICE_TYPE = "DEVICE_TYPE";
    const DEVICE_TOKEN = "DEVICE_TOKEN";
    const USER_ID = "USER_ID";
    const USER_IMG = "USER_IMG";
    const SENDER_ID = "SENDER_ID";
    const RECEIVER_ID = "RECEIVER_ID";
    const MESSAGE = "MESSAGE";
    const SESSION = "SESSION";

    const USER_NAME = "USER_NAME";
    const USER_EMAIL = "USER_EMAIL";
    const PASS_CODE = "PASS_CODE";
    const MOBILE_NUMBER = "MOBILE_NUMBER";
    const FULL_NAME = "FULL_NAME";
	const SUPER_ADMIN = "SUPER_ADMIN";
	const IS_ADMIN_VIEWONLY = "IS_ADMIN_VIEWONLY";
	 const IS_SHIPPER = "IS_SHIPPER";
    const PROFILE_FILENAME = "PROFILE_FILENAME";
    const QB_ID = 'QB_ID';
    const QB_USER_LOGIN = 'QB_USER_LOGIN';
    const QB_PASSWORD = 'QB_PASSWORD';

    // Project
    const PROJECT_ID = "PROJECT_ID";
    const PROJECT_ROLE = "PROJECT_ROLE";
    const PROJECT_NAME = "PROJECT_NAME";
    const PROJECT_DESCRIPTION = "PROJECT_DESCRIPTION";
    const PROJECT_STATUS = "PROJECT_STATUS";
    const PRIVACY_TYPE = "PRIVACY_TYPE";
    const CREATOR_ID = "CREATOR_ID";
    const MODERATOR_ID = "MODERATOR_ID";
    const DUE_DATE = "DUE_DATE";
	const DUE_DATE_DT = "DUE_DATE_DT";
    const UPDATED_DATE = "UPDATED_DATE";
    const CLOSED_DATE = "CLOSED_DATE";
    const ADD_BY_ID = "ADD_BY_ID";
    const MEMBERS_LIST = "MEMBERS_LIST";

    const TASK_ID = "TASK_ID";
    const TASK_TITLE = "TASK_TITLE";
    const PARENT_TASK_ID = "PARENT_TASK_ID";
    const TASK_DESCRIPTION = "TASK_DESCRIPTION";
    const TASK_STATUS = 'TASK_STATUS';
    const STATUS = 'STATUS';
    const ASSIGNED_ID = "ASSIGNED_ID";
    const CREATED_DATE = "CREATED_DATE";
    const REPEAT_INTERVAL = "REPEAT_INTERVAL";
    const CC = "CC";
    const CURRENT_DATE = 'CURRENT_DATE';
    const TYPE = 'TYPE';

    const SHIPMENT_ID = "SHIPMENT_ID";
    const SHIPMENT_TITLE = "SHIPMENT_TITLE";
    const SHIPMENT_DESCRIPTION = "SHIPMENT_DESCRIPTION";
    const SHIPMENT_CATEGORY = "SHIPMENT_CATEGORY";
    const CUSTOMER_NAME = "CUSTOMER_NAME";
    const INVOICE_NUMBER = "INVOICE_NUMBER";
    const SHIPMENT_STATUS = "SHIPMENT_STATUS";
    const SHIPMENT_IMAGE_ID = "SHIPMENT_IMAGE_ID";
    const SHIPMENT_IMAGE = "SHIPMENT_IMAGE";
    const SHIPMENT_LOG_ID = "SHIPMENT_LOG_ID";
    const SHIPMENT_NUMBER = "SHIPMENT_NUMBER";
    const REASON = "REASON";

    // settings
    const SETTINT_ID = "SETTING_ID";
    const SETTING_TYPE = "SETTING_TYPE";
    const SETTING_STATUS = "SETTING_STATUS";
    const OBJECT_ID = "OBJECT_ID";
    const NOTIFICATION = "NOTIFICATION";
    const NOTIFICATION_TYPE = "NOTIFICATION_TYPE";
    const OBJECT_TYPE = "OBJECT_TYPE";

    //QuickBlox
    const APPLICATION_ID = "78375";
    const AUTH_KEY = "npvmnubpT5-ZWY4";
    const AUTH_SECRET = "7cAaOfg6bPPSKGH";
    const USER_LOGIN = "taskplanner";
    const USER_PASSWORD = "taskplanner123";
    const QB_API_ENDPOINT = "https://api.quickblox.com";
    const QB_PATH_SESSION = "session.json";
    const QB_USER_SIGNUP = "users.json";
    const QB_GET_USER_BY_LOGIN = "users/by_login.json?login=";
    const QB_DIALOG_CREATE = "chat/Dialog.json";
}