<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'timezone' => 'Asia/Karachi',   // Your timezone
            'level' => \Monolog\Logger::DEBUG,
        ],

        /*'primary' => [
            'host' => 'localhost',
            'dbname' => 'urdutech_taskdb',
            'user' => 'urdutech_taskusr',
            'pass' => 'vV4u9noXgz@C',
        ],*/

        'primary' => [
            'host' => 'localhost',
            'dbname' => 'asawebsu_taskapi2',
            'user' => 'root',
            'pass' => '123456',
        ],
    ],
    'commands' => [
        'TaskNotifications' => \Controllers\TaskNotifications::class,
        'TaskIntervalNotifications' => \Controllers\TaskIntervalNotifications::class,
        'ChangeStatusOfRepeatedTasks' => \Controllers\ChangeStatusOfRepeatedTasks::class,
        'OverDueTaskNotifications'  => \Controllers\OverDueTaskNotifications::class,
    ]
];
