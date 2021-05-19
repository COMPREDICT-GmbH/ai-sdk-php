<?php


namespace Compredict\API\Algorithms\Resources;

use Compredict\API\Algorithms\Resource;

class Version extends Resource
{

    /**
     * Result of the last run of predict method, it could be:
     * - null if never ran.
     * - Task if the predict escalated to task.
     * - Prediction if the results are directly given.
     * - false if the request failed and setOnFailure is false.
     *
     * @var null|Task|Prediction|false
     */
    private $last_result;


    public function __construct($object = false, $client = null)
    {
        parent::__construct($object, $client);
        if (is_null($this->algorithm_id)) {
            throw new \UnexpectedValueException("Please set algorithm_id");
        }
        $this->last_result = null;
    }

    public function predict(
        $data,
        $evaluate = true,
        $encrypt = false,
        $callback_param = null,
        $callback = null
    ) {
        $this->last_result = $this->client->getPrediction(
            $this->algorithm_id,
            $data,
            $evaluate,
            $encrypt,
            $callback,
            $callback_param,
            $this->version
        );

        return $this->last_result;
    }

    public function getDetailedTemplate($type = 'input')
    {
        $this->client->getTemplate($this->algorithm_id, $type, $this->version);
    }

    public function getDetailedGraph($type = 'input')
    {
        $this->client->getGraph($this->algorithm_id, $type, $this->version);
    }

    public function getResponseTime(): ?string
    {
        return $this->results;
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
