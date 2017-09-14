<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
		],
		'test_db' => [
			'db_name' => 'test',
			'collection_name' => 'test',
			'dns' => 'mongodb://localhost:27017'
		],
		'db' => [
			'db_name' => 'trellish',
			'collection_name' => 'tasks',
			'dns' => 'mongodb://localhost:27017'
		],
		'memcached' => [
			'servers' => [
				['host' => '127.0.0.1', 'port' => 11211, 'weight' => 0]
			]
		]
    ],
];
