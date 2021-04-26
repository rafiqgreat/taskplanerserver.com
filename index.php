<?php
if (PHP_SAPI == 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

session_start();
require __DIR__ . '/vendor/autoload.php';

// Instantiate the app
$settings = require __DIR__ . '/src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/src/dependencies.php';

// Register middleware
require __DIR__ . '/src/middleware.php';

// Register routes
require __DIR__ . '/src/routes.php';

$container['view'] = function ($container) {
    return new \Slim\Views\PhpRenderer('templates/');
};

$base_url = ($_SERVER['REMOTE_ADDR'] == '::1') ? 'http://localhost/www/taskplannerserver.com/' : 'http://localhost/www/taskplannerserver.com/';

$app->get('/', function ($request, $response) use ($base_url){
    if(!isset($_SESSION['logged_in']) && empty($_SESSION['logged_in'])) {
        return $response->withStatus(302)->withHeader('Location', $base_url . 'login');
    } else {
        return $this->view->render($response, 'index.php', array('url' => $base_url));
    }

});

$app->get('/login', function ($request, $response) use ($base_url) {
    if(isset($_SESSION['logged_in']) && !empty($_SESSION['logged_in'])) {
        return $response->withStatus(302)->withHeader('Location', $base_url);
    } else {
        return $this->view->render($response, 'login.php', array('url' => $base_url));
    }
});

$app->get('/logout', function ($request, $response) use ($base_url) {
    session_destroy();

    echo 'SUCCESS';
});

$app->add(new \Slim\HttpCache\Cache('public', 28800));
// Run app
$app->run();
?>