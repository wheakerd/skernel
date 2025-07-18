<?php
ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');
ini_set('memory_limit', '1G');

error_reporting(E_ALL);

!defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

// TODO: Self-called anonymous function that creates its own scope and keep the global namespace clean.
(function () {
	Hyperf\Di\ClassLoader::init();
	/** @var Psr\Container\ContainerInterface $container */
	$container = require BASE_PATH . '/config/container.php';

	$application = $container->get(Hyperf\Contract\ApplicationInterface::class);
	$application->run();
})();