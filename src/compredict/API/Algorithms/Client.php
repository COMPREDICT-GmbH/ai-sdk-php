<?php

namespace Compredict\API\Algorithms;

use \Exception as Exception;

class Client
{

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
    //protected $baseURL = 'https://aic.compredict.de/api/';
    protected $baseURL = 'localhost:8800/api/';

    /**
     * API version
     *
     * @var string
     **/
    protected $APIVersion = 'v1';

    /**
     * Callback to receive the results of the long term processing.
     *
     * @var string
     **/
    protected $callback_url;

    /**
     * Private key to decrypt messages.
     *
     * @var Openssl RSA Resource
     */
    protected $ppk = false;

    public function __construct($token = null, $callback_url = null, $ppk = null, $passphrase = "")
    {
        if (!isset($token) || strlen($token) !== 40) {
            throw new Exception("A 40 character API Key must be provided");
        }

        if (!is_null($callback_url) && !filter_var($callback_url, FILTER_VALIDATE_URL)) {
            throw new Exception("URL provided is not valid");
        }

        $this->api_key = $token;
        $this->http = new Request($this->baseURL . $this->APIVersion);
        $this->callback_url = $callback_url;
        $this->http->setToken($token);
        if (!is_null($ppk)) {
            $this->setPrivateKey($ppk, $passphrase);
        }
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
        if (!filter_var($callback_url, FILTER_VALIDATE_URL)) {
            throw new Exception("URL provided is not valid");
        }

        $this->callback_url = $callback_url;
    }

    /**
     * Configure the API client to throw exceptions when HTTP errors occur.
     *
     * Note that network faults will always cause an exception to be thrown.
     *
     * @param bool $option sets the value of this flag
     */
    public function failOnError($option = true)
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
     * @param  Boolean option to enable/disable SSL.
     */
    public function verifyPeer($option)
    {
        return $this->http->verifyPeer($option);
    }

    /**
     * Function to set the Private key that will be used to decrypt the messages.
     *
     * @param string $keyPath path to the key .ppm file.
     * @param string $passphrase for the given key.
     */
    public function setPrivateKey($keyPath, $passphrase = "")
    {
        $fp = fopen($keyPath, 'r');
        $ppk_str = fread($fp, 8192);
        fclose($fp);
        $this->ppk = openssl_pkey_get_private($ppk_str, $passphrase);
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
        if ($object === false || is_string($object)) {
            return $object;
        }

        $baseResource = __NAMESPACE__ . '\\' . $resource;
        $class = (class_exists($baseResource)) ? $baseResource : 'Compredict\\API\\Algorithms\\Resources\\' . $resource;
        return new $class($object, $this);
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
        if ($object === false || is_string($object)) {
            return $object;
        }

        $baseResource = __NAMESPACE__ . '\\' . $resource;
        $resource_class = (class_exists($baseResource)) ? $baseResource : 'Compredict\\API\\Algorithms\\Resources\\' . $resource;
        $array_of_resources = array();
        foreach ($object as $res) {
            array_push($array_of_resources, new $resource_class($object, $this));
        }
        return $array_of_resources;
    }

    /**
     * Returns the default collection of algorithms.
     *
     * @return mixed array|string list of algorithms.
     */
    public function getAlgorithms()
    {
        $response = $this->http->GET('/algorithms');
        if ($response === false || is_string($response)) {
            return $response;
        }
        return new Resources\Algorithms($response, $this);
    }

    /**
     * Returns the default Resource of algorithm.
     *
     * @param String $algorithm_id.
     * @return Resource/Algorithm object.
     */
    public function getAlgorithm($algorithm_id)
    {
        $response = $this->http->GET("/algorithms/{$algorithm_id}");
        return $this->mapResource('Algorithm', $response);
    }

    /**
     * Returns the default Resource of Task.
     *
     * @param String $task_id
     * @return Resource/Algorithm object.
     */
    public function getTaskResult($task_id)
    {
        $response = $this->http->GET("/algorithms/tasks/{$task_id}");
        return $this->mapResource('Task', $response);
    }

    /**
     * Returns the default Resource of Task after cancelation.
     *
     * @param String $task_id
     * @return Resource/Algorithm object.
     */
    public function cancelTask($task_id)
    {
        $response = $this->http->DELETE("/algorithms/tasks/{$task_id}");
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
    public function getPrediction($algorithm_id, $data, $evaluate = true, $encrypt = false)
    {
        $requset_files = ['features' => ['fileName' => 'featuers.json', 'fileContent' => json_encode($data)]];
        $request_data = ['evaluate' => $this->_process_evaluate($evaluate), 'encrypt' => $encrypt];
        if (!is_null($this->callback_url)) {
            $request_data['callback_url'] = $this->callback_url;
        }

        $response = $this->http->post("/algorithms/{$algorithm_id}/predict", $request_data, $requset_files);
        // need to check if prediction or task.
        $resource = (isset($response->job_id)) ? 'Task' : 'Prediction';
        return $this->mapResource($resource, $response);
    }

    /**
     * Convert the evaluate parameter to the correct format before sending the request.
     *
     * @param  bool|array|string $evaluate parameter
     * @return bool|string
     */
    protected function _process_evaluate($evaluate)
    {
        if (is_array($evaluate)) {
            return json_encode($evaluate);
        }

        return $evaluate;
    }

    /**
     * Downloads the detailed file.
     *
     * @param String $algorithm_id
     * @param String $type describes the file type whether `input` or `output`
     */
    public function getTemplate($algorithm_id, $type = 'input')
    {
        $response = $this->http->GET("/algorithms/{$algorithm_id}/template?type={$type}");
        if ($this->http->getHttpCode() == 200) {
            // to download the file.
            header("Content-type: application/json");
            header("Content-Disposition: attachment; filename={$algorithm_id}-{$type}-template.json");
            header("Pragma: no-cache");
            header("Expires: 0");
            echo $response;
            return true;
        }
        return false;
    }

    /**
     * Downloads the detailed input graph of the algorithm.
     *
     * @param String $algorithm_id
     * @param String $type describes the file type whether `input` or `output`
     */
    public function getGraph($algorithm_id, $type = 'input')
    {
        $response = $this->http->GET("/algorithms/{$algorithm_id}/graph?type={$type}");
        if ($this->http->getHttpCode() == 200) {
            // to download the file.
            header("Content-type: image/png");
            header("Content-Disposition: attachment; filename={$algorithm_id}-graph.png");
            header("Pragma: no-cache");
            header("Expires: 0");
            echo $response;
            return true;
        }
        return false;
    }

    /**
     * @param  base64_encoded string holds the encrypted message.
     * @param  integer Chunking by bytes to feed to the decryptor algorithm.
     * @return String decrypted message.
     */
    public function RSADecrypt($encrypted_msg, $chunk_size = 256)
    {
        if (is_null($this->ppk)) {
            throw new Exception("Returned message is encrypted while you did not provide private key!");
        }

        $encrypted_msg = base64_decode($encrypted_msg);

        $offset = 0;
        $chunk_size = 256;

        $decrypted = "";
        while ($offset < strlen($encrypted_msg)) {
            $decrypted_chunk = "";
            $chunk = substr($encrypted_msg, $offset, $chunk_size);

            if (openssl_private_decrypt($chunk, $decrypted_chunk, $this->ppk, OPENSSL_PKCS1_OAEP_PADDING)) {
                $decrypted .= $decrypted_chunk;
            } else {
                var_dump($decrypted);
                throw new exception("Problem decrypting the message.");
            }
            $offset += $chunk_size;
        }
        return $decrypted;
    }

}
