<?php

class TasksApiController {

	private $request;
	private $response;
	private $container;

	public function __construct($request, $response, $container) {
		$this->request = $request;
		$this->response = $response;
		$this->container = $container;
	}

	public function test() {
		$test = $this->container->test_collection;
		$array = $test->find()->toArray();

		return $this->response->withJson($array, 200);
	}

	public function create() {
		$data = $this->request->getParsedBody();

		if (!array_key_exists('title', $data) || !array_key_exists('due_date', $data)) {
			return $this->error_status('Required fields missing', 400, $data);
		}

		$data['completed'] = false;
		$data['created_at'] = $data['updated_at'] = time();
		$data['due_time'] = strtotime($data['due_date']);

		try {
			$tasks_collection = $this->container->tasks_collection;

			$result = $tasks_collection->insertOne($data);
			$data['_id'] = $result->getInsertedId();

			return $this->response->withJson($data, 200);
		} catch(Exception $e) {
			return $this->error_status($e->getMessage(), 500);
		}
	}

	public function update($id) {
		$data = $this->request->getParsedBody();
	
		// Remove id just in case
		unset($data['_id']);
		$data['updated_at'] = time();

		try {
			$tasks_collection = $this->container->tasks_collection;

			$updateResult = $tasks_collection->updateOne(['_id' => new MongoDB\BSON\ObjectID($id)], ['$set' => $data]);

			if ($updateResult->getMatchedCount() > 0) {
				return $this->response->withStatus(200);
			} else {
				return $this->response->withStatus(404);
			}
		} catch(Exception $e) {
			return $this->error_status($e->getMessage(), 500);
		}
	}

	public function mark_complete($id) {
		return $this->response->withJson(['marked' => true], 200);
	}

	public function mark_incomplete($id) {
		return $this->response->withJson(['marked' => true], 200);
	}

	public function show($id) {
		try {
			$tasks_collection = $this->container->tasks_collection;
			$task = $tasks_collection->findOne(['_id' => new MongoDB\BSON\ObjectID($id)]);

			if (is_null($task)) {
				return $this->response->withStatus(404);
			} else {
				return $this->response->withJson($task, 200);
			}
		} catch(Exception $e) {
			return $this->error_status($e->getMessage(), 500);
		}
	}

	public function list() {
		return $this->response->withJson($tasks, 200);
	}

	public function delete($id) {
		return $this->response;
	}

	private function error_status($message, $error_code, $data = null) {
		$error_data = [
			'error_message' => $message,
		];

		if (!is_null($data)) {
			$error_data['data_received'] = $data;
		}

		return $this->response->withJson($error_data, $error_code);
	}
}
