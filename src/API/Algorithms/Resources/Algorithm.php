<?php

namespace Compredict\API\Algorithms\Resources;

use Compredict\API\Algorithms\Resource;

class Algorithm extends Resource
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
        $this->last_result = null;
        $versions = [];
        print_r($this->versions);
        foreach ($this->get_versions() as $key => $version) {
            $version->algorithm_id = $this->id;
            array_push($versions, new Version($version, $this->client));
        }
        $this->versions = $versions;
    }

    public function predict(
        $data,
        $evaluate = true,
        $encrypt = false,
        $callback_param = null,
        $callback = null,
        $version = null
    ) {
        echo "version " . $version . " version";
        $this->last_result = $this->client->getPrediction(
            $this->id,
            $data,
            $evaluate,
            $encrypt,
            $callback_param,
            $callback,
            $version
        );

        return $this->last_result;
    }

    public function get_versions(): ?array
    {
        return $this->versions;
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
        return (count($this->versions) > 0) ? $this->versions[0]->getResponseTime() : null;
    }

    public function getTemplate($type = 'input')
    {
        if (count($this->versions) == 0) {
            return null;
        }

        return $this->versions[0] . get_template($type);
    }

    public function getLastPredictions()
    {
        return $this->last_result;
    }
}
