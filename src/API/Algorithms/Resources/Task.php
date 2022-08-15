<?php

namespace Compredict\API\Algorithms\Resources;

use Compredict\API\Algorithms\Resource;

class Task extends Resource
{
    const STATUS_PENDING = "Pending";
    const STATUS_PROGRESS = "In Progress";
    const STATUS_FINISHED = "Finished";
    const STATUS_CANCELED = "Canceled";

    public function __construct($object = false, $client = null)
    {
        parent::__construct($object, $client);
        $this->status = $this->status ?? self::STATUS_PENDING;
        $this->success = $this->success ?? null;
        $this->error = $this->error ?? null;
        $this->is_encrypted = $this->is_encrypted ?? false;
        $this->setResults($this->predictions, $this->evaluations, $this->monitors);
    }

    public function update()
    {
        $task = $this->client->getTaskResult($this->job_id);
        $this->fields = $task->fields;

        return $this;
    }

    public function cancel()
    {
        $task = $this->client->cancelTask($this->job_id);
        $this->status = $task->status;

        return $task->fields->is_canceled;
    }

    public function getCurrentStatus()
    {
        return $this->status;
    }

    protected function setResults($predictions, $evaluations, $monitors)
    {
        $this->predictions = null;
        $this->evaluations = null;
        $this->monitors = null;
        if ($this->status == self::STATUS_FINISHED && $this->success) {
            if ($this->is_encrypted) {
                $this->predictions = $this->client->RSADecrypt($predictions);
                $this->evaluations = $this->client->RSADecrypt($evaluations);
                $this->monitors = $this->client->RSADecrypt($monitors);
            } else {
                $this->predictions = $predictions;
                $this->evaluations = $evaluations;
                $this->monitors = $monitors;
            }
        }
    }
}
