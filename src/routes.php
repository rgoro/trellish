<?php

require_once('Controller/TasksApiController.php');

// Routes
$app->get('/test/', function ($request, $response, $args) {
	return (new TasksApiController($request, $response, $this))->test();
});

$app->post('/create/', function ($request, $response, $args) {
	return (new TasksApiController($request, $response, $this))->create();
});

$app->put('/update/{id}', function ($request, $response, $args) {
	return (new TasksApiController($request, $response, $this))->update($args['id']);
});

$app->put('/mark_complete/{id}', function ($request, $response, $args) {
	return (new TasksApiController($request, $response, $this))->mark_complete($args['id']);
});

$app->put('/mark_incomplete/{id}', function ($request, $response, $args) {
	return (new TasksApiController($request, $response, $this))->mark_incomplete($args['id']);
});

$app->get('/task/{id}/', function ($request, $response, $args) {
	return (new TasksApiController($request, $response, $this))->show($args['id']);
});

$app->get('/tasks/', function ($request, $response, $args) {
	return (new TasksApiController($request, $response, $this))->list();
});

$app->delete('/delete/{id}/', function ($request, $response, $args) {
	return (new TasksApiController($request, $response, $this))->delete($args['id']);
});

$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});


