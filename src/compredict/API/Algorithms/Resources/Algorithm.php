<?php

namespace Compredict\API\Algorithms\Resources;

use Compredict\API\Algorithms\Client;
use Compredict\API\Algorithms\Resource;

class Algorithm extends Resource
{

    public function __construct($object = false, $client = null)
    {
        parent::__construct($object, $client);
        $this->last_result = null;
    }

    public function predict($data, $evaluate = true, $encrypt = false)
    {
        $this->last_result = $this->client->getPrediction($this->id, $data, $evaluate, $encrypt);
        return $this->last_result;
    }

    public function getDetailedTemplate($type = 'input')
    {
        $this->client->getTemplate($this->id, $type);
    }

    public function getDetailedGraph($type = 'input')
    {
        $this->client->getGraph($this->id, $type);
    }

    public function getResponseTime()
    {
        return $this->result;
    }

    public function getTemplate($type = 'input')
    {
        return ($type == 'output') ? $this->output_format : $this->features_format;
    }

    public function getLastPredictions()
    {
        return $this->last_result;
    }
}
