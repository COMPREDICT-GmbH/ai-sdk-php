<?php

namespace CompredictAICore\Api;

use \Exception as Exception;

class Client
{
    use SingletonTrait;

    /**
    * Request instance.
    *
    * @var Request
    **/
    protected $http;

    /**
    * API token for authentication.
    *
    * @var string
    **/
    protected $api_key;

    /**
    * Server base url
    *
    * @var string
    **/
    protected $baseURL = 'localhost:8800/api/';
    
    /**
    * API version
    *
    * @var string
    **/
    protected $APIVersion  = 'v1';

    /**
    * Callback to receive the results of the long term processing.
    *
    * @var string
    **/
    protected $callback_url;

    private function __construct($token=null, $callback_url=null)
    {
        if(!isset($token) || strlen($token) !== 40)
            throw new Exception("A 40 character API Key must be provided");

        if (!is_null($callback_url) && !filter_var($callback_url, FILTER_VALIDATE_URL))
            throw new Exception("URL provided is not valid");

        $this->api_key = $token;
        $this->http = new Request($this->baseURL . $this->APIVersion);
        $this->callback_url = $callback_url;
        $this->http->setToken($token);
    }

    /**
     * Get the callback url.
     */
    public function getCallbackUrl()
    {
        return $this->callback_url;
    }

    /**
     * Set the callback url.
     */
    public function setCallbackUrl($callback_url)
    {
        if (!filter_var($callback_url, FILTER_VALIDATE_URL))
            throw new Exception("URL provided is not valid");
        $this->callback_url = $callback_url;
    }

    /**
     * Configure the API client to throw exceptions when HTTP errors occur.
     *
     * Note that network faults will always cause an exception to be thrown.
     *
     * @param bool $option sets the value of this flag
     */
    public function failOnError($option=true)
    {
        $this->http->failOnError($option);
    }

    /**
     * Get error message returned from the last API request if
     * failOnError is false (default).
     *
     * @return string
     */
    public function getLastError()
    {
        return $this->http->getLastError();
    }

    /**
     * Map a single object to a resource class.
     *
     * @param string $resource name of the resource class
     * @param \stdClass $object
     * @return Resource
     */
    private function mapResource($resource, $object)
    {
        if($object == false || is_string($object))
            return $object;

        $baseResource = __NAMESPACE__ . '\\' . $resource;
        $class = (class_exists($baseResource)) ? $baseResource : 'CompredictAICore\\Api\\Resources\\' . $resource;
        return new $class($object);
    }

    /**
     * Internal method to wrap items in a collection to resource classes.
     *
     * @param string $resource name of the resource class
     * @param array $object object collection
     * @return array
     */
    private function mapCollection($resource, $object)
    {
        if($object == false || is_string($object))
            return $object;

        $baseResource = __NAMESPACE__ . '\\' . $resource;
        $resource_class = (class_exists($baseResource)) ? $baseResource : 'CompredictAICore\\Api\\Resources\\' . $resource;
        $array_of_resources = array();
        foreach($object as $res){
            array_push($array_of_resources, new $resource_class($object));
        }
        return $array_of_resources;
    }

    /**
     * Returns the default collection of algorithms.
     *
     * @return mixed array|string list of algorithms.
     */
    public function getAlgorithms(){
        $response = $this->http->GET('/algorithms');
        return $this->mapCollection('Algorithm', $response);
    }

    /**
     * Returns the default Resource of algorithm.
     *
     * @param String $algorithm_id.
     * @return Resource/Algorithm object.
     */
    public function getAlgorithm($algorithm_id){
        $response = $this->http->GET("/algorithms/{$algorithm_id}");
        return $this->mapResource('Algorithm', $response);
    }

    /**
     * Returns the default Resource of Task.
     *
     * @param String $task_id
     * @return Resource/Algorithm object.
     */
    public function getTaskResult($task_id){
        $response = $this->http->GET("/algorithms/tasks/{$task_id}");
        return $this->mapResource('Task', $response);
    }

    /**
     * Run the algorithm on the given data.
     *
     * @param String task_id
     * @param Array $data to predict
     * @param Boolean $evaluate whether to apply standard evaluation or not.
     * @return Resource/Task if the job is escalated to the queue or Resource/Prediction if given instantly.
     */
    public function getPrediction($algorithm_id, $data, $evaluate=True){
        $request_data = ['features' => $data, 'evaluate' => $evaluate];
        if(!is_null($this->callback_url))
            $request_data['callback_url'] = $this->callback_url;
        $response = $this->http->post("/algorithms/{$algorithm_id}/predict", json_encode($request_data));
        // need to check if prediction or task.
        $resource = (isset($response->predictions)) ? 'Prediction' : 'Task';
        return $this->mapResource($resource, $response);
    }

    /**
     * Downloads the detailed file.
     *
     * @param String $algorithm_id
     */
    public function getTemplate($algorithm_id){
        $response = $this->http->GET("/algorithms/{$algorithm_id}/template");
        # to download the file.
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=template.csv");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo $response;
    }

}