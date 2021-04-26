<?php
// DIC configuration

$container = $app->getContainer();

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

$container['cache'] = function () {
    return new \Slim\HttpCache\CacheProvider();
};

$container['primary'] = function ($c) {
    $settings = $c['settings']['primary'];
    $pdo = new PDO("mysql:host=" . $settings['host'] . ";dbname=" . $settings['dbname'], $settings['user'], $settings['pass']);
    $pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$container['\Controllers\AuthenticationController'] = function($c) {
    return new \Controllers\AuthenticationController($c);
};

$container['\Controllers\UserController'] = function($c) {
    return new \Controllers\UserController($c);
};

$container['\Controllers\TaskController'] = function($c) {
    return new \Controllers\TaskController($c);
};

$container['\Controllers\ProjectController'] = function($c) {
    return new \Controllers\ProjectController($c);
};

$container['\Controllers\AssignedTasksController'] = function($c) {
    return new \Controllers\AssignedTasksController($c);
};