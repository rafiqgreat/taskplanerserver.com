<?php
// Routes
$app->group('/api2', function () use ($app) {
    $app->group('/auth', function () use ($app) {
        $app->post('/register', 'Controllers\AuthenticationController:registerUser');
        $app->get('/verify', 'Controllers\AuthenticationController:verifyAuth');
        $app->get('/login', 'Controllers\AuthenticationController:loginUser');
        $app->post('/newlogin', 'Controllers\AuthenticationController:newloginapi');
        $app->post('/session/{id}', 'Controllers\AuthenticationController:renewSessionId');
    });

    $app->group('/users', function () use ($app) {
        $app->get('/{username}', 'Controllers\UserController:getUserDetail');
        $app->post('/update-user', 'Controllers\UserController:updateUser');
        $app->post('/update-settings', 'Controllers\UserController:updateSettings');
        $app->post('/update-device-token', 'Controllers\UserController:updateDeviceToken');
        $app->post('/verify-person-contact', 'Controllers\UserController:verifyPersonContact');
        //$app->post('/user-signup-quickblox', 'Controllers\UserController:userSignupQuickblox');

        //$app->get('/fetch-notifications', 'Controllers\TaskController:fetchNotifications');
        //$app->get('/assigned-projects-tasks', 'Controllers\AssignedTasksController:getAssignProjectsTasks');
    });

    $app->group('/chat', function () use ($app) {
        $app->post('/send-message', 'Controllers\UserController:sendMessage');
        $app->get('/fetch-messages', 'Controllers\UserController:fetchMessages');
    });

    $app->group('/projects', function () use ($app) {
        $app->post('/create', 'Controllers\ProjectController:createProject');
		$app->post('/create-project', 'Controllers\ProjectController:createProjectRafiq');
        $app->post('/delete', 'Controllers\ProjectController:deleteProject');
        $app->post('/update-status', 'Controllers\ProjectController:updateProjectStatus');
        $app->post('/update-project-member-role', 'Controllers\ProjectController:updateProjectMemberRole');
        $app->get('/fetch-member-projects', 'Controllers\ProjectController:getUserProjects');
        $app->get('/fetch-tasks', 'Controllers\TaskController:fetchProjectTasks');
		 $app->get('/fetch-sub-tasks', 'Controllers\TaskController:fetchSubTasks');
		 $app->post('/get-user-mobile', 'Controllers\ProjectController:getUserMoible');
		 $app->post('/get-registered-users', 'Controllers\ProjectController:getRegisteredUsers');
		 $app->get('/get-registered-users-search', 'Controllers\ProjectController:getRegisteredUsersSearch');
		 //getProjectDetail
		 $app->get('/get-project-detail', 'Controllers\ProjectController:getProjectDetail');
		//$app->get('/fetch-project-tasks', 'Controllers\ProjectController:fetchProjectTasksNew'); // Rafiq
        $app->group('/members', function () use ($app) {
            $app->get('/list', 'Controllers\ProjectController:getProjectMemberss');
            $app->post('/manage', 'Controllers\ProjectController:addProjectMembers');
            $app->post('/add', 'Controllers\ProjectController:addProjectMember');
            $app->post('/delete', 'Controllers\ProjectController:deleteMember');
        });
    });

    $app->group('/tasks', function () use ($app) {
        $app->post('/create', 'Controllers\TaskController:createTasks');
		$app->post('/create-ptask', 'Controllers\TaskController:createProjTasks');
		$app->post('/create-task', 'Controllers\TaskController:createTasksWeb');
        $app->post('/delete', 'Controllers\TaskController:deleteTask');
        $app->post('/update', 'Controllers\TaskController:updateTask');
        $app->post('/create/personal', 'Controllers\TaskController:createPersonalTask');
		$app->get('/count-tasks-dd', 'Controllers\TaskController:countTasksDD'); //10/2/2020
		$app->get('/count-tasks', 'Controllers\TaskController:countTasks'); //10/2/2020
        $app->get('/fetch-tasks', 'Controllers\TaskController:fetchTasks'); //fetchDueTasks
		 $app->get('/fetch-due-tasks', 'Controllers\TaskController:fetchDueTasks'); //fetchDueTasks
        $app->get('/fetch-project-due-tasks', 'Controllers\TaskController:fetchProjectDueTasks');
        $app->get('/fetch-tasks-status', 'Controllers\TaskController:fetchStatusTasks');
        $app->get('/fetch-personal-tasks', 'Controllers\TaskController:fetchPersonalTasks');
		 $app->get('/fetch-only-personal-tasks', 'Controllers\TaskController:fetchOnlyPersonalTasks');
        $app->get('/fetch-user-tasks', 'Controllers\TaskController:fetchUserTasks');
        $app->get('/fetch-sub-tasks', 'Controllers\TaskController:fetchSubTasks');
        $app->get('/fetch-cc-tasks', 'Controllers\TaskController:fetchCCTasks');
		$app->get('/fetch-cc-tasks-only', 'Controllers\TaskController:fetchCCTasksOnly');//2/7/2020
		$app->get('/fetch-cc-tasks-interval', 'Controllers\TaskController:fetchCCTasksInterval');
		$app->get('/fetch-only-cc-tasks', 'Controllers\TaskController:fetchOnlyCCTasks');// fetchOnlyCCTasks
		$app->get('/fetch-only-cc-tasks-dd', 'Controllers\TaskController:fetchOnlyCCTasksDD');//1/2/2020
		$app->get('/fetch-cc-tasks-users', 'Controllers\TaskController:fetchCCTasksUsers');//2/5/2020
		$app->get('/fetch-cc-due-tasks-users', 'Controllers\TaskController:fetchCCDueTasksUsers');//1/2/2020
		$app->get('/fetch-cc-due-tasks-interval', 'Controllers\TaskController:fetchCCDueTasksInterval');//1/2/2020
		$app->get('/fetch-cc-due-tasks', 'Controllers\TaskController:fetchCCDueTasks');//fetchCCDueTasks
        $app->post('/update-status', 'Controllers\TaskController:updateTaskStatus');
        $app->post('/assign-task', 'Controllers\TaskController:assignTask');
        $app->post('/create-task-group-qb', 'Controllers\TaskController:createQbGroup');
		$app->get('/fetch-others-tasks', 'Controllers\TaskController:fetchOthersTasks');
		$app->get('/fetch-others-tasks-dd', 'Controllers\TaskController:fetchOthersTasksDD');
		$app->get('/fetch-me-tasks', 'Controllers\TaskController:fetchMeTasks');
		
		$app->get('/get-popnotifications', 'Controllers\TaskController:fetchPopupNotifications');


		//tasks/fetch-member-details
		$app->get('/fetch-member-details', 'Controllers\TaskController:fetchMemberDetails');
		
        // Delete from cc
        $app->post('/delete_from_cc', 'Controllers\TaskController:removeFromCCTask');

        // Upload Task Images
        $app->post('/upload_task_images', 'Controllers\TaskController:uploadTaskImages');

        // task or project details by id
        $app->get('/fetch-details', 'Controllers\TaskController:fetchDetails');

        $app->get('/fetch-notifications', 'Controllers\TaskController:fetchNotifications');

        // notification test
        $app->post('/member-added-notify-test', 'Controllers\TaskController:taskNotificationTest');

        // notification push task before due time
        $app->get('/push-notification-cron', 'Controllers\TaskController:pushNotificationCron');

        // notification push task on repeat interval
        $app->get('/push-interval-notification-cron', 'Controllers\TaskController:pushNotificationOnInterval');

        // change status of repeated tasks
        $app->get('/change-status-repeated-tasks', 'Controllers\ChangeStatusOfRepeatedTasks:command');

        //turn off notifications for particular task
        $app->post('/notification-status', 'Controllers\TaskController:changeTaskNotificationStatus');
		// search tasks
		 $app->get('/search-tasks', 'Controllers\TaskController:searchTasks');
		$app->get('/adv-search-tasks', 'Controllers\TaskController:advSearchTasks');
		 $app->post('/update-project-task', 'Controllers\TaskController:changeProjectTaskDetails');
		 

    });

    $app->group('/shipments', function () use ($app) {
        $app->post('/create', 'Controllers\ShipmentController:createShipment');
        $app->post('/update', 'Controllers\ShipmentController:updateShipment');
        $app->post('/delete', 'Controllers\ShipmentController:deleteShipment');
        $app->get('/fetch-shipments', 'Controllers\ShipmentController:fetchShipments');
        $app->get('/update-status', 'Controllers\ShipmentController:updateShipmentStatus');
        $app->get('/update-final-status', 'Controllers\ShipmentController:updateShipmentFinalStatus');
        $app->post('/create-shipment-group-qb', 'Controllers\ShipmentController:createQbGroup');
		
		//getShipmentUsersList = Rafiq
		 $app->get('/fetch-shipment-users', 'Controllers\ShipmentController:getShipmentUsersList');
		 $app->get('/fetch-shipment-group', 'Controllers\ShipmentController:getShipmentUsersGroup');
		  $app->get('/fetch-shipment-group-search', 'Controllers\ShipmentController:getShipmentUsersGroupSearch');

        // task or project details by id
        $app->get('/fetch-details', 'Controllers\ShipmentController:fetchDetails');

        // Upload Shipment Images
        $app->post('/upload_shipment_images', 'Controllers\ShipmentController:uploadShipmentImages');
    });
});