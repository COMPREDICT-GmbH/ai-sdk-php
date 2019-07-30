<?php

namespace Compredict\API\Algorithms\Resources;

use Compredict\API\Algorithms\Client;
use Compredict\API\Algorithms\Resource;

class Task extends Resource
{

    const STATUS_PENDING = "Pending";
    const STATUS_PROGRESS = "In Progress";
    const STATUS_FINISHED = "Finished";

    public function __construct($object = false)
    {
        parent::__construct($object);
        $this->status = $this->status ?? self::STATUS_PENDING;
        $this->success = $this->success ?? null;
        $this->error = $this->error ?? null;
        $this->is_encrypted = $this->is_encrypted ?? false;
        $this->setResults($this->predictions, $this->evaluations);
    }

    public function update()
    {
        $task = $this->client->getTaskResult($this->job_id);
        $this->fields = $task->fields;
        return $this;
    }

    public function getCurrentStatus()
    {
        return $this->status;
    }

    protected function setResults($predictions, $evaluations)
    {
        $this->predictions = null;
        $this->evaluations = null;
        if ($this->status == self::STATUS_FINISHED && $this->success) {
            if ($this->is_encrypted) {
                $this->predictions = $this->client->RSADecrypt($predictions);
                $this->evaluations = $this->client->RSADecrypt($evaluations);
            } else {
                $this->predictions = $predictions;
                $this->evaluations = $evaluations;
            }
        }
    }
}
