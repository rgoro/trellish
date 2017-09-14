<?php
// DIC configuration
$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// Mongo
$container['test_collection'] = function ($c) {
	$settings = $c->get('settings')['test_db'];

	$client = new MongoDB\Client($settings['dns']);

	$database = $client->{$settings['db_name']};

	$collection = $database->{$settings['collection_name']};

	return $collection;
};

$container['tasks_collection'] = function ($c) {
	$settings = $c->get('settings')['db'];

	$client = new MongoDB\Client($settings['dns']);

	$database = $client->{$settings['db_name']};

	$collection = $database->{$settings['collection_name']};

	return $collection;
};

$container['memcached'] = function ($c) {
	$settings = $c->get('settings')['memcached'];
	$m = new \Memcached();
	foreach ($settings['servers'] as $s) {
		$m->addServer($s['host'], $s['port'], $s['weight']);
	}

	return $m;
};
