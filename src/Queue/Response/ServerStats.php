<?php
namespace GMO\Beanstalk\Queue\Response;

class ServerStats extends AbstractStats {

	//region Current Counts

	/**
	 * The number of ready jobs with priority < 1024
	 * @return int
	 */
	public function currentUrgentJobs() {
		return $this->get('current-jobs-urgent');
	}

	/**
	 * The number of jobs in the ready queue
	 * @return int
	 */
	public function currentReadyJobs() {
		return $this->get('current-jobs-ready');
	}

	/**
	 * The number of jobs reserved by all clients
	 * @return int
	 */
	public function currentReservedJobs() {
		return $this->get('current-jobs-reserved');
	}

	/**
	 * The number of delayed jobs
	 * @return int
	 */
	public function currentDelayedJobs() {
		return $this->get('current-jobs-delayed');
	}

	/**
	 * The number of buried jobs
	 * @return int
	 */
	public function currentBuriedJobs() {
		return $this->get('current-jobs-buried');
	}

	/**
	 * The number of currently existing tubes
	 * @return int
	 */
	public function currentTubes() {
		return $this->get('current-tubes');
	}

	/**
	 * The number of currently open connections
	 * @return int
	 */
	public function currentConnections() {
		return $this->get('current-connections');
	}

	/**
	 * The number of open connections that have each
	 * issued at least one put command
	 * @return int
	 */
	public function currentProducers() {
		return $this->get('current-producers');
	}

	/**
	 * The number of open connections that have each
	 * issued at least one reserve command
	 * @return int
	 */
	public function currentWorkers() {
		return $this->get('current-workers');
	}

	/**
	 * The number of open connections that have issued
	 * a reserve command but not yet received a response
	 * @return int
	 */
	public function currentWaiting() {
		return $this->get('current-waiting');
	}

	//endregion

	//region Command Counts

	/**
	 * The cumulative number of put commands
	 * @return int
	 */
	public function putCount() {
		return $this->get('cmd-put');
	}

	/**
	 * The cumulative number of peek commands
	 * @return int
	 */
	public function peekCount() {
		return $this->get('cmd-peek');
	}

	/**
	 * The cumulative number of peek ready commands
	 * @return int
	 */
	public function peekReadyCount() {
		return $this->get('cmd-peek-ready');
	}

	/**
	 * The cumulative number of peek delayed commands
	 * @return int
	 */
	public function peekDelayedCount() {
		return $this->get('cmd-peek-delayed');
	}

	/**
	 * The cumulative number of peek buried commands
	 * @return int
	 */
	public function peekBuriedCount() {
		return $this->get('cmd-peek-buried');
	}

	/**
	 * The cumulative number of reserve commands
	 * @return int
	 */
	public function reserveCount() {
		return $this->get('cmd-reserve');
	}

	/**
	 * The cumulative number of use commands
	 * @return int
	 */
	public function useCount() {
		return $this->get('cmd-use');
	}

	/**
	 * The cumulative number of watch commands
	 * @return int
	 */
	public function watchCount() {
		return $this->get('cmd-watch');
	}

	/**
	 * The cumulative number of ignore commands
	 * @return int
	 */
	public function ignoreCount() {
		return $this->get('cmd-ignore');
	}
	/**
	 * The cumulative number of delete commands
	 * @return int
	 */
	public function deleteCount() {
		return $this->get('cmd-delete');
	}
	/**
	 * The cumulative number of release commands
	 * @return int
	 */
	public function releaseCount() {
		return $this->get('cmd-release');
	}

	/**
	 * The cumulative number of bury commands
	 * @return int
	 */
	public function buryCount() {
		return $this->get('cmd-bury');
	}

	/**
	 * The cumulative number of kick commands
	 * @return int
	 */
	public function kickCount() {
		return $this->get('cmd-kick');
	}

	/**
	 * The cumulative number of stats commands
	 * @return int
	 */
	public function statsCount() {
		return $this->get('cmd-stats');
	}

	/**
	 * The cumulative number of stats job commands
	 * @return int
	 */
	public function statsJobCount() {
		return $this->get('cmd-stats-job');
	}

	/**
	 * The cumulative number of stats tube commands
	 * @return int
	 */
	public function statsTubeCount() {
		return $this->get('cmd-stats-tube');
	}

	/**
	 * The cumulative number of list tubes commands
	 * @return int
	 */
	public function listTubesCount() {
		return $this->get('cmd-list-tubes');
	}

	/**
	 * The cumulative number of list tubes used commands
	 * @return int
	 */
	public function listTubesUsedCount() {
		return $this->get('cmd-list-tube-used');
	}

	/**
	 * The cumulative number of list tubes watched commands
	 * @return int
	 */
	public function listTubeWatchedCount() {
		return $this->get('cmd-list-tubes-watched');
	}

	/**
	 * The cumulative number of pause tube commands
	 * @return int
	 */
	public function pauseTubeCount() {
		return $this->get('cmd-pause-tube');
	}

	//endregion

	/**
	 * The cumulative count of times a job has timed out
	 * @return int
	 */
	public function jobTimeouts() {
		return $this->get('job-timeouts');
	}

	/**
	 * The cumulative count of jobs created
	 * @return int
	 */
	public function totalJobs() {
		return $this->get('total-jobs');
	}

	/**
	 * The maximum number of bytes in a job
	 * @return int
	 */
	public function maxJobSize() {
		return $this->get('max-job-size');
	}

	/**
	 * The cumulative count of connections
	 * @return int
	 */
	public function totalConnections() {
		return $this->get('total-connections');
	}

	/**
	 * A random id string for this server process,
	 * generated when each beanstalkd process starts
	 * @return string
	 */
	public function id() {
		return $this->get('id');
	}

	/**
	 * The process id of the server
	 * @return int
	 */
	public function pid() {
		return $this->get('pid');
	}

	/**
	 * The version string of the server
	 * @return string
	 */
	public function version() {
		return $this->get('version');
	}

	/**
	 * The hostname of the machine as determined by uname
	 * @return string
	 */
	public function hostname() {
		return $this->get('hostname');
	}

	/**
	 * The cumulative user CPU time of this process
	 * in seconds and microseconds
	 * @return float
	 */
	public function userCpuUsage() {
		return $this->get('rusage-utime');
	}

	/**
	 * The cumulative system CPU time of this process
	 * in seconds and microseconds
	 * @return float
	 */
	public function systemCpuUsage() {
		return $this->get('rusage-stime');
	}

	/**
	 * The number of seconds since this server process started running
	 * @return int
	 */
	public function upTime() {
		return $this->get('uptime');
	}

	//region Binlog

	/**
	 * The index of the oldest binlog file needed to store the current jobs
	 * @return int
	 */
	public function binlogOldestIndex() {
		return $this->get('binlog-oldest-index');
	}

	/**
	 * The index of the current binlog file being written to.
	 * If binlog is not active this value will be 0.
	 * @return int
	 */
	public function binlogCurrentIndex() {
		return $this->get('binlog-current-index');
	}

	/**
	 * The maximum size in bytes a binlog file is allowed to get before a new binlog file is opened
	 * @return int
	 */
	public function binlogMaxSize() {
		return $this->get('binlog-max-size');
	}

	/**
	 * The cumulative number of records written to the binlog
	 * @return int
	 */
	public function binlogRecordsWritten() {
		return $this->get('binlog-records-written');
	}

	/**
	 * The cumulative number of records written as part of compaction
	 * @return int
	 */
	public function binlogRecordsMigrated() {
		return $this->get('binlog-recrods-migrated');
	}

	//endregion
}
