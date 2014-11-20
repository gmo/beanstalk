<?php
namespace GMO\Beanstalk\Job;

use Pheanstalk\PheanstalkInterface;

interface JobProducerInterface {

	const HIGH_PRIORITY = 512;
	const DEFAULT_PRIORITY = 1024;
	const LOW_PRIORITY = 1536;

	const DEFAULT_DELAY = PheanstalkInterface::DEFAULT_DELAY;
	const DEFAULT_TTR = PheanstalkInterface::DEFAULT_TTR;

	/**
	 * Pushes a job to the specified tube
	 * @param string   $tube     Tube name
	 * @param \GMO\Common\ISerializable|\Traversable|array|mixed $data Job data
	 * @param int|null $priority From 0 (most urgent) to 4294967295 (least urgent)
	 * @param int|null $delay    Seconds to wait before job becomes ready
	 * @param int|null $ttr      Time To Run: seconds a job can be reserved for
	 * @return int The new job ID
	 */
	public function push($tube, $data, $priority = null, $delay = null, $ttr = null);
}
