<?php
namespace GMO\Beanstalk\Queue\Response;

class JobStats extends AbstractStats {

	/**
	 * The job id
	 * @return int
	 */
	public function id() {
		return $this->get('id');
	}

	/**
	 * The name of the tube that contains this job
	 * @return string
	 */
	public function tube() {
		return $this->get('tube');
	}

	/**
	 * "ready" or "delayed" or "reserved" or "buried"
	 * @return string
	 */
	public function state() {
		return $this->get('state');
	}

	/**
	 * The priority value set by the put, release, or bury commands
	 * @return int
	 */
	public function priority() {
		return $this->get('pri');
	}

	/**
	 * The time in seconds since the put command that created this job
	 * @return int
	 */
	public function age() {
		return $this->get('age');
	}

	/**
	 * The number of seconds left until the server puts this job into
	 * the ready queue. This number is only meaningful if the job is
	 * reserved or delayed. If the job is reserved and this amount of time
	 * elapses before its state changes, it is considered to have timed out.
	 * @return int
	 */
	public function timeLeft() {
		return $this->get('time-left');
	}

	/**
	 * The number of the earliest binlog file containing this job.
	 * If -b wasn't used, this will be 0.
	 * @return int
	 */
	public function file() {
		return $this->get('file');
	}

	/**
	 * The number of times this job has been reserved
	 * @return int
	 */
	public function reserves() {
		return $this->get('reserves');
	}

	/**
	 * The number of times this job has timed out during a reservation
	 * @return int
	 */
	public function timeouts() {
		return $this->get('timeouts');
	}

	/**
	 * The number of times a client has released this job from a reservation
	 * @return int
	 */
	public function releases() {
		return $this->get('releases');
	}

	/**
	 * The number of times this job has been buried
	 * @return int
	 */
	public function buries() {
		return $this->get('buries');
	}

	/**
	 * The number of times this job has been kicked
	 * @return int
	 */
	public function kicks() {
		return $this->get('kicks');
	}
}
