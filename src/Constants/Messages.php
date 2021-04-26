<?php
/**
 * Created by IntelliJ IDEA.
 * User: nayab
 * Date: 22-Sep-17
 * Time: 8:53 PM
 */
namespace Constants;


class Messages
{
    const MSG_REQUEST_SUCCESSFUL = "HTTP Request has been resolved.";

    //General Error Message
    const MSG_ERR_PARAMS_VALUE_MISSING = "HTTP Params values are missing.";

    const MSG_ERR_DB_CONNECTION = 'Database is temporarily down or not running!';
    const MSG_ERR_HTTP_PARAMS = 'Missing/Invalid HTTP Parameters!';
    const MSG_ERR_AUTH_HTTP_PARAMS = 'Auth & Session ids missing or invalid!';
    const MSG_ERR_AUTH_CODE = 'Invalid Session or Authorization Code!';

    const MSG_ERR_DEVICE_ID_REQUIRED = "Device ID is required!";
    const MSG_ERR_DEVICE_AUTH_REQUIRED = "Device ID or Auth Token are required!";
    const MSG_ERR_AUTH_CREATE = "Unable to create Auth Token!";
    const MSG_ERR_EMAIL_FORMAT = "Invalid Email Format!";
    const MSG_ERR_PHONE = "Invalid Phone Number!";
    
    const MSG_ERR_USER_EXISTS = "Mobile/Email address already exists!";
    const MSG_ERR_INVALID_USER = "Invalid Mobile/Email Address and Password!";

    const MSG_ERR_ACCOUNT_PENDING = "User account is in pending status!";
    const MSG_ERR_ACCOUNT_DISABLED = "User account has been disabled!";
    const MSG_ERR_ACCOUNT_INACTIVE = "User account is inactive!";

    const MSG_ERR_DUPLICATE_PROJECT = "Duplicate project found with same name!";
    const MSG_ERR_PROJECT_CREATE = "Unable to create project!";
    const MSG_ERR_TASK_CREATE = "Unable to create task!";

    const MSG_ERR_PROJECT_FETCH = "Unable to fetch projects or no projects found!";
    const MSG_ERR_PROJECT_MEMBER_FETCH = "No projects members found.";
    const MSG_ERR_PROJECT_TASK_FETCH = "Unable to fetch project tasks or no task found!";

    const MSG_ERR_DUPLICATE_TASK_ASSIGNED_TO = "Task is duplicate error using same Assigned ID";

    //Success
    const MSG_SUCCESS_AUTH_TOKEN = "Auth Token has been verified!";
    const MSG_SUCCESS_LOGIN = "Login verified successfully!";

const MSG_SUCCESS_CREATE_TASK = "Task created successfully!";


    // Notifications
    const NTF_TASK_MEMBER_ADDED = "NEW_TASK_MEMBER_ADDED";
    const NTF_TASK_MEMBER_ADDED_MSG = ":subject added you in task :object";
}