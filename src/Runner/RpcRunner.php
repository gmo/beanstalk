<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Runner;

use Gmo\Beanstalk\Job\Job;

/**
 * Abstracts the repetitive worker tasks for Remote Procedure Call (RPC).
 * RPC involves doing work and sending the result back to the producer.
 * This cleans up the concrete worker and allows it to focus on processing jobs.
 */
class RpcRunner extends BaseRunner
{
    const RPC_REPLY_TO_FIELD = 'rpcReplyTo';

    /** @var string The tube to send the reply back to */
    private $replyTo;

    public function preProcessJob(Job $job)
    {
        $job = parent::preProcessJob($job);
        $this->replyTo = $job->getData()->remove(static::RPC_REPLY_TO_FIELD);
        $job->setData($job->getData()->get('data'));

        return $job;
    }

    public function postProcessJob(Job $job)
    {
        parent::postProcessJob($job);

        if (!$this->replyTo) {
            return;
        }

        if (!$this->isTubeWatched($this->replyTo)) {
            $this->log->debug("No one is listening, not pushing to return queue");

            return;
        }

        $data = array('result' => $job->getResult());
        $this->queue->push($this->replyTo, $data);
    }

    /**
     * Returns true if the tube has at least one watcher
     *
     * @param string $tube
     *
     * @return bool
     */
    private function isTubeWatched($tube)
    {
        if (!$this->doesTubeExist($tube)) {
            return false;
        }

        return $this->queue->statsTube($tube)->watchingCount() > 0;
    }

    /**
     * Returns true if the tube exists
     *
     * @param string $tube
     *
     * @return bool
     */
    private function doesTubeExist($tube)
    {
        $maxRetry = 3;
        $retry = 0;
        do {
            if ($retry > 0) {
                usleep(100000);
            }

            $tubeList = $this->queue->tubes();

            if ($tubeList->has($tube)) {
                return true;
            }
            $retry++;
        } while ($retry < $maxRetry);

        return false;
    }
}
