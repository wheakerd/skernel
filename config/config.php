<?php
declare(strict_types=1);

use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Log\LogLevel;

use function Hyperf\Support\env;

return [
	'app_name'                   => env('APP_NAME', 'skernel'),
	'app_env'                    => env('APP_ENV', 'dev'),
	'scan_cacheable'             => env('SCAN_CACHEABLE', false),
	StdoutLoggerInterface::class => [
		'log_level' => [
			LogLevel::ALERT,
			LogLevel::CRITICAL,
//			LogLevel::DEBUG,
			LogLevel::EMERGENCY,
			LogLevel::ERROR,
			LogLevel::INFO,
			LogLevel::NOTICE,
			LogLevel::WARNING,
		],
	],
];
