<?php
namespace GMO\Beanstalk\Queue;

use GMO\Beanstalk\Helper\JobDataSerializer;
use GMO\Beanstalk\Job\Job;
use GMO\Beanstalk\Job\NullJob;
use GMO\Beanstalk\Job\RabbitJob;
use GMO\Beanstalk\Job\RabbitManagementJob;
use GMO\Beanstalk\Queue\Response\JobStats;
use GMO\Beanstalk\Queue\Response\ServerStats;
use GMO\Beanstalk\Queue\Response\TubeStats;
use GMO\Beanstalk\Tube\Tube;
use GMO\Beanstalk\Tube\TubeCollection;
use GMO\Common\Collections\ArrayCollection;
use GMO\Common\String;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * TODO:
 *  - priority
 *  - delay
 *  - ttr
 *  - job IDs (maybe fake the job ids - fake with data hash)
 *  - clear channel & declared queues if connection fails?
 * Requires:
 *  - https://github.com/rabbitmq/rabbitmq-delayed-message-exchange
 *
 */
class RabbitQueue implements QueueInterface {

	const EXCHANGE = 'my_exchange';
	const BURIED_SUFFIX = '-buried';

	const AMQP_LOW_PRIORITY = 0;
	const AMQP_DEFAULT_PRIORITY = 1;
	const AMQP_HIGH_PRIORITY = 2;

	/** @var AbstractConnection */
	protected $connection;
	/** @var AMQPChannel */
	protected $channel;
	/** @var JobDataSerializer */
	protected $serializer;
	/** @var LoggerInterface */
	protected $logger;
	/** @var RabbitManagement */
	protected $management;

	protected $declaredQueues;

	public static function create($host = 'localhost', $port = 5672, $user = 'guest', $password = 'guest', $vhost = '/') {
		$connection = new AMQPLazyConnection($host, $port, $user, $password, $vhost);
		$management = new RabbitManagement($host, RabbitManagement::DEFAULT_PORT, $user, $password, $vhost);
		return new static($connection, $management);
	}

	/**
	 * RabbitQueue constructor.
	 * @param AbstractConnection $connection
	 */
	public function __construct(AbstractConnection $connection, RabbitManagement $management) {
		$this->connection = $connection;
		$this->serializer = new JobDataSerializer();
		$this->logger = new NullLogger();
		$this->declaredQueues = new ArrayCollection();
		$this->management = $management;
	}

	protected function getChannel() {
		if (!$this->channel) {
			$this->channel = $this->connection->channel(1);
			// ready tube
			$this->channel->exchange_declare(static::EXCHANGE, 'direct', false, true, false);
			// buried tube
			$this->channel->exchange_declare(static::EXCHANGE . static::BURIED_SUFFIX, 'direct', false, true, false);
			// delayed tube
			$this->channel->exchange_declare(static::EXCHANGE . '-delayed', 'direct', false, true, false, false, false, new AMQPTable(array(
				'x-delayed-type' => 'direct',
			)));
		}
		return $this->channel;
	}

	protected function declareQueue($name) {
		$ch = $this->getChannel();
		$ch->queue_declare($name, false, true, false, false, false, new AMQPTable(array(
			//'x-dead-letter-routing-key' => $name . '.buried',
//			'x-max-priority' => static::MAX_PRIORITY, // Maxes out memory and crashes
//			"x-expires"      => 16000 // How long a queue can be unused for before it is automatically deleted (milliseconds)
		)));
		$ch->queue_bind($name, static::EXCHANGE, $name, false, new AMQPTable(array()));
		$ch->queue_bind($name, static::EXCHANGE . static::BURIED_SUFFIX, $name, false, new AMQPTable(array()));
		$ch->queue_bind($name, static::EXCHANGE . '-delayed', $name, false, new AMQPTable(array()));

		/*
		 * $ch->queue_declare('test11', false, true, false, false, false, new AMQPTable(array(
			   "x-dead-letter-exchange" => "t_test1", // error tube
			   "x-message-ttl" => 15000, // apply only to reserved tube
			   "x-expires" => 16000
			)));
		 */
	}

	protected function createMessage($data, $priority) {
		$data = $this->serializer->serialize($data);
		return new AMQPMessage($data, array(
			'content_type'  => 'text/plain',
			'delivery_mode' => 2, // persistent
			'priority'      => $priority,
			'correlation_id' => uniqid(), // ramsey/uuid
//			'content_encoding' => null,
//			'reply_to' => null,
//			'expiration' => null,
//			'message_id' => null,
//			'timestamp' => null,
//			'type' => null,
//			'user_id' => null,
//			'app_id' => null,
//			'cluster_id' => null,
		));
	}

	/**
	 * Pushes a job to the specified tube
	 * @param string                                             $tube     Tube name
	 * @param \GMO\Common\ISerializable|\Traversable|array|mixed $data     Job data
	 * @param int|null                                           $priority From 0 (most urgent) to 4294967295 (least
	 *                                                                     urgent)
	 * @param int|null                                           $delay    Seconds to wait before job becomes ready
	 * @param int|null                                           $ttr      Time To Run: seconds a job can be reserved
	 *                                                                     for
	 * @return int The new job ID
	 */
	public function push($tube, $data, $priority = self::AMQP_DEFAULT_PRIORITY, $delay = null, $ttr = null) {
		$priority = $this->normalizePriority($priority);
		$message = $this->createMessage($data, $priority);
		$this->publish($tube, $message);
	}

	/**
	 * Reserves a job from the specified tube
	 * @param string   $tube
	 * @param int|null $timeout
	 * @param bool     $stopWatching Stop watching the tube after reserving the job
	 * @return Job
	 */
	public function reserve($tube, $timeout = null, $stopWatching = false) {
		$this->declareQueue($tube);
		$ch = $this->getChannel();

		/** @var AMQPMessage $message */
		$message = $ch->basic_get($tube);
		if ($message === null) {
			return new NullJob();
		}

		$data = $this->serializer->unserialize($message->body);
		$job = new RabbitJob($message, $data, Job::STATUS_RESERVED, $this);

		return $job;
	}

	public function release(Job $job, $priority = null, $delay = null) {
		$job = $this->assertRabbitJob($job);
		if ($job->getState() !== Job::STATUS_RESERVED) {
			throw new \LogicException('Only reserved jobs can be released');
		}

		if ($priority !== null) {
			// TODO Not sure if message can be modified
			$job->setPriority($this->normalizePriority($priority));
		}
		if ($delay > 0) {
			// delay job
			$job->setState(Job::STATUS_DELAYED); // Handle transition after time
			$this->publish($job->getTubeName(), $job->getMessage(), $delay);
		} else {
			// requeue job
			$this->getChannel()->basic_reject($job->getDeliveryTag(), true);
			$job->setState(Job::STATUS_READY);
		}
	}

	public function bury(Job $job, $priority = null) {
		$job = $this->assertRabbitJob($job);
		if ($job->getState() !== Job::STATUS_RESERVED) {
			throw new \LogicException('Only reserved jobs can be buried');
		}

		$this->publish($job->getTubeName() . static::BURIED_SUFFIX, $job->getMessage());
		$job->setState(Job::STATUS_BURIED);

		$this->delete($job);
	}

	protected function publish($tube, AMQPMessage $message, $delay = null) {
		$this->declareQueue($tube);

		if ($delay <= 0) {
			$this->getChannel()->basic_publish($message, static::EXCHANGE, $tube);
			return;
		}
		$message->set('application_headers', new AMQPTable(array(
			'x-delay' => $delay,
		)));
		$this->getChannel()->basic_publish($message, static::EXCHANGE . '-delayed', $tube);
	}

	/**
	 * Deletes a job
	 * @param Job $job
	 */
	public function delete($job) {
		$job = $this->assertRabbitJob($job);
		$this->getChannel()->basic_ack($job->getDeliveryTag());
	}

	protected function normalizePriority($priority) {
		if ($priority < 0) {
			throw new \InvalidArgumentException('Priority has to be positive');
		}

		// Assume already normalized
		if ($priority <= static::AMQP_HIGH_PRIORITY) {
			return $priority;
		}

		if ($priority < QueueInterface::DEFAULT_PRIORITY) {
			return static::AMQP_LOW_PRIORITY;
		}
		if ($priority == QueueInterface::DEFAULT_PRIORITY) {
			return static::AMQP_DEFAULT_PRIORITY;
		}
		return static::AMQP_HIGH_PRIORITY;
	}

	/**
	 * @param $job
	 * @return RabbitJob
	 */
	protected function assertRabbitJob($job) {
		if (!$job instanceof RabbitJob) {
			throw new \InvalidArgumentException('Job must be a RabbitJob');
		}
		return $job;
	}

	/**
	 * If the given job exists and is in a buried or delayed state,
	 * it will be moved to the ready queue of the the same tube
	 * where it currently belongs.
	 *
	 * @param Job $job
	 */
	public function kickJob($job) {
		$job = $this->assertRabbitJob($job);
		if ($job->getState() !== Job::STATUS_BURIED) {
			throw new \LogicException('Only buried jobs can be kicked');
		}

		$tube = String::removeLast($job->getTubeName(), static::BURIED_SUFFIX);
		$this->publish($tube, $job->getMessage());
		$job->setState(Job::STATUS_READY);

		$this->delete($job);
	}

	/**
	 * Gives statistical information about the specified job if it exists.
	 *
	 * @param Job|int $job
	 * @return JobStats
	 */
	public function statsJob($job) {

	}

	/**
	 * {@inheritdoc}
	 */
	public function touch(Job $job) {
		//NOPE
	}

	/**
	 * {@inheritdoc}
	 */
	public function setLogger(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	/**
	 * {@inheritdoc}
	 */
	public function tube($name) {
		return new Tube($name, $this);
	}

	/**
	 * {@inheritdoc}
	 */
	public function tubes() {
		$tubes = new TubeCollection();
		foreach ($this->listTubes() as $tubeName) {
			$tubes->set($tubeName, new Tube($tubeName, $this));
		}

		return $tubes;
	}

	/**
	 * {@inheritdoc}
	 */
	public function listTubes() {
		$tubes = $this->management->getQueues();
		$suffix = static::BURIED_SUFFIX;
		$tubes = $tubes->filter(function ($tube) use ($suffix) {
			return !String::endsWith($tube, $suffix);
		});
		return $tubes;
	}

	/**
	 * @param string $tube
	 * @param string $state
	 * @param int    $count
	 *
	 * @return RabbitManagementJob[]
	 *
	 * @throws \GMO\Common\Exception\NotSerializableException
	 */
	protected function listJobs($tube, $state, $count = PHP_INT_MAX) {
		if ($state === Job::STATUS_BURIED) {
			$tube .= static::BURIED_SUFFIX;
		}
		$json = $this->management->getMessages($tube, $count);
		$jobs = array();
		foreach ($json as $message) {
			$amqpMsg = new AMQPMessage($message['payload'], $message['properties']);
			$data = $this->serializer->unserialize($amqpMsg->body);
			$jobs[] = new RabbitManagementJob($amqpMsg, $data, $state, $this);
		}

		return $jobs;
	}

	/**
	 * Gets the stats for all tubes
	 * @return TubeStats[]|ArrayCollection
	 */
	public function statsAllTubes() {

	}

	/**
	 * Returns the stats about the server
	 * @return ServerStats
	 */
	public function statsServer() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function peekJob($jobId) {
		foreach ($this->listTubes() as $tube) {
			foreach ($this->listJobs($tube, Job::STATUS_READY) as $job) {
				if ($job->getId() === $jobId) {
					return $job;
				}
			}
			foreach ($this->listJobs($tube, Job::STATUS_BURIED) as $job) {
				if ($job->getId() === $jobId) {
					return $job;
				}
			}
		}

		return new NullJob();
	}

	/**
	 * Kicks all jobs in a given tube.
	 * Buried jobs will be kicked before delayed jobs
	 * @param string $tube
	 * @param int    $num Number of jobs to kick, -1 is all
	 * @return int number of jobs deleted
	 */
	public function kickTube($tube, $num = -1) {

	}

	/**
	 * Inspect the next ready job in the specified tube
	 * @param string $tube
	 * @return Job|NullJob
	 */
	public function peekReady($tube) {

	}

	/**
	 * Inspect the next buried job in the specified tube
	 * @param string $tube
	 * @return Job|NullJob
	 */
	public function peekBuried($tube) {

	}

	/**
	 * Inspect the next delayed job in the specified tube
	 * @param string $tube
	 * @return Job|NullJob
	 */
	public function peekDelayed($tube) {

	}

	/**
	 * Deletes all ready jobs in a given tube
	 * @param string $tube
	 * @param int    $num Number of jobs to delete, -1 is all
	 * @return int number of jobs deleted
	 */
	public function deleteReadyJobs($tube, $num = -1) {

	}

	/**
	 * Deletes all buried jobs in a given tube
	 * @param string $tube
	 * @param int    $num Number of jobs to delete, -1 is all
	 * @return int number of jobs deleted
	 */
	public function deleteBuriedJobs($tube, $num = -1) {

	}

	/**
	 * Deletes all delayed jobs in a given tube
	 * @param string $tube
	 * @param int    $num Number of jobs to delete, -1 is all
	 * @return int number of jobs deleted
	 */
	public function deleteDelayedJobs($tube, $num = -1) {

	}

	/**
	 * Temporarily prevent jobs being reserved from the given tube
	 *
	 * @param string $tube  The tube to pause
	 * @param int    $delay Seconds before jobs may be reserved from this queue.
	 */
	public function pause($tube, $delay) {

	}

	/**
	 * Gets the stats for the given tube
	 * @param string $tube
	 * @return TubeStats
	 */
	public function statsTube($tube) {

	}
}
