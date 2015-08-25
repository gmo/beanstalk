<?php
namespace GMO\Beanstalk\Queue;

use GMO\Common\Collections\ArrayCollection;

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
		$queues = new ArrayCollection($response->json());
		$queues = $queues->map(function ($queue) {
			return $queue['name'];
		});

		return $queues;
	}

	public function getQueue($name) {
		$request = $this->guzzle->get("queues/{$this->vhost}/{$name}");
		$response = $request->send();
		$queue = $response->json();
		return $queue;
	}

	public function getMessages($queue, $count = PHP_INT_MAX) {
		$request = $this->guzzle->post("queues/{$this->vhost}/{$queue}/get", array(), json_encode(array(
			'count'    => $count,
			'requeue'  => true,
			'encoding' => 'auto',
		)));
		$response = $request->send();
		$json = $response->json();
		return $json;
	}

	public function purgeQueue($name) {
		$request = $this->guzzle->delete("queues/{$this->vhost}/{$name}/contents");
		$request->send();
	}
}
