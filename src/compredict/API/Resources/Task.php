<?php

namespace Compredict\API\Resources;

use Compredict\API\Client;
use Compredict\API\Resource;

class Task extends Resource
{

    public function __construct($object = false)
    {
        parent::__construct($object);
        $this->status = $this->status ?? "pending";
        $this->success = $this->success ?? null;
        $this->error = $this->error ?? null;
        $this->predictions = $this->predictions ?? null;
        $this->evalutaions = $this->evalutaions ?? null;
    }

    public function getLatestUpdate(){
        $task = $this->client->getTaskResult($this->job_id);
        $this->fields = $task->fields;
        return $this->predictions;
    }

    public function getCurrentStatus(){
        return $this->status;
    }
}