<?php
namespace GMO\Beanstalk\Queue;

class RabbitManagement {

	const DEFAULT_PORT = 15672;

	/** @var \Guzzle\Http\ClientInterface */
	protected $guzzle;
	protected $vhost;

	public function __construct($host = 'localhost', $port = self::DEFAULT_PORT, $user = 'guest', $password = 'guest', $vhost = '/') {
		$this->guzzle = new \Guzzle\Http\Client("http://{$host}:{$port}/api/", array(
			'request.options' => array(
				'auth' => array($user, $password),
			),
		));
		if ($vhost === '/') {
			$vhost = '%2F';
		}
		$this->vhost = $vhost;
	}

	public function getQueues() {
		$request = $this->guzzle->get("queues/{$this->vhost}");
		$response = $request->send();
		$queues = $response->json();
		return $queues;
	}

	public function getQueue($name) {
		$request = $this->guzzle->get("queues/{$this->vhost}/{$name}");
		$response = $request->send();
		$queue = $response->json();
		return $queue;
	}

	public function getMessages($queue) {
		$request = $this->guzzle->post("queues/{$this->vhost}/{$queue}/get", array(), json_encode(array(
			'count'    => PHP_INT_MAX,
			'requeue'  => true,
			'encoding' => 'auto',
		)));
		$response = $request->send();
		$messages = $response->json();
		return $messages;
	}

	public function purgeQueue($name) {
		$request = $this->guzzle->delete("queues/{$this->vhost}/{$name}/contents");
		$request->send();
	}
}
