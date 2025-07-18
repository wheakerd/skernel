<?php
declare(strict_types=1);

return [
	'default' => [
		'handler'   => [
			'class'       => Monolog\Handler\StreamHandler::class,
			'constructor' => [
				'stream' => BASE_PATH . '/runtime/logs/hyperf.log',
				'level'  => Monolog\Level::Debug,
			],
		],
		'formatter' => [
			'class'       => Monolog\Formatter\LineFormatter::class,
			'constructor' => [
				'format'                => null,
				'dateFormat'            => 'Y-m-d H:i:s',
				'allowInlineLineBreaks' => true,
			],
		],
	],
];
