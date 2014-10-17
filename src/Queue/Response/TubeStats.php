<?php
namespace GMO\Beanstalk\Queue\Response;

class TubeStats extends AbstractStats {

	/**
	 * The tube's name
	 * @return string
	 */
	public function name() {
		return $this->get('name');
	}

	/**
	 * The number of ready jobs with priority < 1024 in this tube
	 * @return int
	 */
	public function urgentJobs() {
		return $this->get('current-jobs-urgent');
	}

	/**
	 * The number of jobs in the ready queue in this tube
	 * @return int
	 */
	public function readyJobs() {
		return $this->get('current-jobs-ready');
	}

	/**
	 * the number of jobs reserved by all clients in this tube
	 * @return int
	 */
	public function reservedJobs() {
		return $this->get('current-jobs-reserved');
	}

	/**
	 * The number of delayed jobs in this tube
	 * @return int
	 */
	public function delayedJobs() {
		return $this->get('current-jobs-delayed');
	}

	/**
	 * The number of buried jobs in this tube
	 * @return int
	 */
	public function buriedJobs() {
		return $this->get('current-jobs-buried');
	}

	/**
	 * The cumulative count of jobs created in this tube
	 * in the current beanstalkd process
	 * @return int
	 */
	public function totalJobs() {
		return $this->get('total-jobs');
	}

	/**
	 * The number of open connections that are currently using this tube
	 * @return int
	 */
	public function usingCount() {
		return $this->get('current-using');
	}

	/**
	 * The number of open connections that have issued a reserve command
	 * while watching this tube but not yet received a response
	 * @return int
	 */
	public function waitingCount() {
		return $this->get('current-waiting');
	}

	/**
	 * The number of open connections that are currently watching this tube
	 * @return int
	 */
	public function watchingCount() {
		return $this->get('current-watching');
	}

	/**
	 * The number of seconds the tube has been paused for
	 * @return int
	 */
	public function pause() {
		return $this->get('pause');
	}

	/**
	 * The number of seconds until the tube is un-paused
	 * @return int
	 */
	public function pauseTimeLeft() {
		return $this->get('pause-time-left');
	}

	/**
	 * The cumulative number of delete commands for this tube
	 * @return int
	 */
	public function cmdDeleteCount() {
		return $this->get('cmd-delete');
	}

	/**
	 * The cumulative number of pause-tube commands for this tube
	 * @return int
	 */
	public function cmdPauseTubeCount() {
		return $this->get('cmd-pause-tube');
	}
}
