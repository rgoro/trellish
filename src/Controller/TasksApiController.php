<?php

class TasksApiController {

	private $request;
	private $response;
	private $container;

	const PAGE_SIZE = 5;

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

		$due_time = strtotime($data['due_date']);
		if ($due_time === false) {
			return $this->error_status('Invalid due date', 400, $data);
		}

		$data['completed'] = false;
		$data['created_at'] = $data['updated_at'] = time();
		$data['due_time'] = $due_time;

		try {
			$tasks_collection = $this->container->tasks_collection;

			$result = $tasks_collection->insertOne($data);
			$data['_id'] = $result->getInsertedId();

			return $this->response->withJson($data, 200);
		} catch(Exception $e) {
			return $this->error_status($e->getMessage(), 500);
		}
	}

	private function do_update($id, $data) {
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

	public function update($id) {
		$data = $this->request->getParsedBody();

		return $this->do_update($id, $data);
	}

	public function mark_complete($id) {
		return $this->do_update($id, ['completed' => true]);
	}

	public function mark_incomplete($id) {
		return $this->do_update($id, ['completed' => false]);
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
		$params = $this->request->getQueryParams();

		$query = [];
		if (array_key_exists('duedate_from', $params)) {
			if (strtotime($params['duedate_from']) !== false) {
				$query['due_time']['$gte'] = strtotime($params['duedate_from']);
			} else if (intval($params['duedate_from']) > 0) {
				$query['due_time']['$gte'] = intval($params['duedate_from']);
			} else {
				return $this->error_status('Invalid min. due date', 400, $params);
			}
		}
		if (array_key_exists('duedate_to', $params)) {
			if (strtotime($params['duedate_to']) !== false) {
				$query['due_time']['$lte'] = strtotime($params['duedate_to']);
			} else if (intval($params['duedate_to']) > 0) {
				$query['due_time']['$lte'] = intval($params['duedate_to']);
			} else {
				return $this->error_status('Invalid max. due date', 400, $params);
			}
		}

		if (array_key_exists('created_from', $params)) {
			if (strtotime($params['created_from']) !== false) {
				$query['created_at']['$gte'] = strtotime($params['created_from']);
			} else if (intval($params['created_from']) > 0) {
				$query['created_at']['$gte'] = intval($params['created_from']);
			} else {
				return $this->error_status('Invalid min. creation date', 400, $params);
			}
		}
		if (array_key_exists('created_to', $params)) {
			if (strtotime($params['created_to']) !== false) {
				$query['created_at']['$lte'] = strtotime($params['created_to']);
			} else if (intval($params['created_to']) > 0) {
				$query['created_at']['$lte'] = intval($params['created_to']);
			} else {
				return $this->error_status('Invalid max. creation date', 400, $params);
			}
		}

		if (array_key_exists('updated_from', $params)) {
			if (strtotime($params['updated_from']) !== false) {
				$query['updated_at']['$gte'] = strtotime($params['updated_from']);
			} else if (intval($params['updated_from']) > 0) {
				$query['updated_at']['$gte'] = intval($params['updated_from']);
			} else {
				return $this->error_status('Invalid min. creation date', 400, $params);
			}
		}
		if (array_key_exists('updated_to', $params)) {
			if (strtotime($params['updated_to']) !== false) {
				$query['updated_at']['$lte'] = strtotime($params['updated_to']);
			} else if (intval($params['updated_to']) > 0) {
				$query['updated_at']['$lte'] = intval($params['updated_to']);
			} else {
				return $this->error_status('Invalid max. update date', 400, $params);
			}
		}

		// Notice that 'onlycomplete' overrides 'onlyincomplete'
		if (array_key_exists('onlycomplete', $params)) {
			$query['complete'] = true;
		} else if (array_key_exists('onlyincomplete', $params)) {
			$query['complete'] = false;
		}

		$options = ['limit' => self::PAGE_SIZE];
		if (array_key_exists('page', $params)) {
			$options['skip'] = $params['page'] * self::PAGE_SIZE;
		}

		try {
			$tasks_collection = $this->container->tasks_collection;
			$tasks = $tasks_collection->find($query, $options);
			return $this->response->withJson($tasks->toArray(), 200);
		} catch(Exception $e) {
			return $this->error_status($e->getMessage(), 500);
		}
	}

	// This could also be implemented as a virtual delete, updating a 'deleted' field
	public function delete($id) {
		try {
			$tasks_collection = $this->container->tasks_collection;

			$deleteResult = $tasks_collection->deleteOne(['_id' => new MongoDB\BSON\ObjectID($id)]);

			if ($deleteResult->getDeletedCount() > 0) {
				return $this->response->withStatus(200);
			} else {
				return $this->response->withStatus(404);
			}
		} catch(Exception $e) {
			return $this->error_status($e->getMessage(), 500);
		}
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
