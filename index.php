<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

session_start();

require __DIR__ . '/vendor/autoload.php';

use Respect\Validation\Validator as v;

$app = new \Slim\App([
	'settings' => [
		'displayErrorDetails' => true,
		"db" => [
            "host" => "localhost",
            "dbname" => "myDb",
            "user" => "root",
            "pass" => "qwerty"
        ],
	]
]);

$container = $app->getContainer();

$container['view'] = function($container) {
	
	$view = new \Slim\Views\Twig(__DIR__ . '/app/views', [
		'cache' => false,
	]);

	$view->addExtension(new \Slim\Views\TwigExtension(
		$container->router,
		$container->request->getUri()
	));

	// $view->getEnvironment()->addGlobal('auth', [
	// 	'check' => $container->auth->check(),
	// 	'user' => $container->auth->user(),
	// ]);

	// $view->getEnvironment()->addGlobal('flash', $container->flash);

	return $view;
};

$container['HomeController'] = function ($container) {
	return new \App\Controllers\HomeController($container);
};

$container['AuthController'] = function ($container) {
	return new \App\Controllers\AuthController($container);
};

v::with('App\\Validation\\Rules\\');

require __DIR__ . '/app/config/routes.php';

$app->run();