<?php

namespace Compredict\API\Algorithms;

use Exception as Exception;
use stdClass;
use UnexpectedValueException;

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
    protected $baseURL = 'https://core.compredict.ai/api/';

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
     * @var resource|false RSA Resource
     */
    protected $ppk = false;

    public function __construct($token = null, $callback_url = null, $ppk = null, $passphrase = "", $http = null)
    {
        if (! isset($token) || strlen($token) !== 40) {
            throw new UnexpectedValueException("A 40 character API Key must be provided");
        }

        if (! is_null($callback_url) && ! filter_var($callback_url, FILTER_VALIDATE_URL)) {
            throw new UnexpectedValueException("URL provided is not valid");
        }

        $this->api_key = $token;
        $this->http = $http ?? new Request($this->baseURL . $this->APIVersion);
        $this->callback_url = $callback_url;
        $this->http->setToken($token);

        if (! is_null($ppk)) {
            $this->setPrivateKey($ppk, $passphrase);
        }
    }

    /**
     * Get the callback url.
     */
    public function getCallbackUrl(): ?string
    {
        return $this->callback_url;
    }

    /**
     * Set the callback url.
     * @param $callback_url
     * @throws Exception
     */
    public function setCallbackUrl($callback_url)
    {
        if (! filter_var($callback_url, FILTER_VALIDATE_URL)) {
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
     * @return mixed json decoded response
     */
    public function getLastError()
    {
        return $this->http->getLastError();
    }

    /**
     * @param Boolean option to enable/disable SSL.
     */
    public function verifyPeer($option)
    {
        $this->http->verifyPeer($option);
    }

    /**
     * Set the url to COMPREDICT AIC server.
     * @param String $url
     */
    public function setURL(string $url)
    {
        $this->http->setURL($url);
    }

    /**
     * Get the url to COMPREDICT AIC server
     * @return String URL
     */
    public function getURL(): string
    {
        return $this->http->getURL();
    }

    /**
     * Function to set the Private key that will be used to decrypt the messages.
     *
     * @param string $keyPath path to the key .ppm file.
     * @param string $passphrase for the given key.
     */
    public function setPrivateKey(string $keyPath, $passphrase = "")
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
     * @param stdClass|false|string $object
     * @return Resource | false
     */
    private function mapResource(string $resource, $object)
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
     * @param stdClass|false|string $object
     * @return array|false|string
     */
    private function mapCollection(string $resource, $object)
    {
        if ($object === false || is_string($object)) {
            return $object;
        }

        $baseResource = __NAMESPACE__ . '\\' . $resource;
        $resource_class = (class_exists($baseResource)) ? $baseResource : 'Compredict\\API\\Algorithms\\Resources\\' . $resource;
        $array_of_resources = [];

        foreach ($object as $res) {
            array_push($array_of_resources, new $resource_class($res, $this));
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
     * @param String $algorithm_id .
     * @return Resource/Algorithm object.
     */
    public function getAlgorithm(string $algorithm_id)
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
    public function getTaskResult(string $task_id)
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
    public function cancelTask(string $task_id)
    {
        $response = $this->http->DELETE("/algorithms/tasks/{$task_id}");

        return $this->mapResource('Task', $response);
    }

    /**
     * Run the algorithm on the given data.
     *
     * @param String $algorithm_id
     * @param array | String $data to predict
     * @param Boolean $evaluate whether to apply standard evaluation or not.
     * @param Boolean $encrypt to indicate whether the sent data is encrypt or not.
     * @param null $callback_param Additional parameters that a requester will receive in the callback url or
     * when requesting the results.
     * @param null $callback URL that overrides the main $this->callback for receiving endpoint of data.
     * @param null | String $version algorithm's version to be requested, if null, then latest version is requested.
     * @param string $file_content_type
     * @return Resource/Task if the job is escalated to the queue or Resource/Prediction if given instantly.
     */
    public function getPrediction(
        string $algorithm_id,
        $data,
        $evaluate = true,
        $encrypt = false,
        $callback_param = null,
        $callback = null,
        $version = null,
        $file_content_type = "application/json"
    ) {
        if (is_string($data)) {
            $request_files =
                [
                    'features' => curl_file_create($data, $file_content_type, 'featuers.json'),
                ];
        } else {
            $request_files = ['features' => ['fileName' => 'featuers.json', 'fileContent' => json_encode($data)]];
        }

        $request_data = ['evaluate' => $this->_process_evaluate($evaluate), 'encrypt' => $encrypt, 'callback_param' => json_encode($callback_param)];

        if (! is_null($callback)) {
            $request_data['callback_url'] = $callback;
        } elseif (! is_null($this->callback_url)) {
            $request_data['callback_url'] = $this->callback_url;
        }

        if (! is_null($version)) {
            $request_data['version'] = $version;
        }

        $response = $this->http->POST("/algorithms/{$algorithm_id}/predict", $request_data, $request_files);
        // need to check if prediction or task.
        $resource = (isset($response->job_id)) ? 'Task' : 'Prediction';

        return $this->mapResource($resource, $response);
    }

    /**
     * Train fit algorithm with passed data.
     *
     * @param string $algorithm_id string identifier of the algorithm.
     * @param array | string $data to predict.
     * @param string $version choose the version of the algorithm you would like to call. Default is latest version.
     * @param bool $export_new_version trained model will be exported to a new version if true.
     *         Otherwise, the requested version will be updated. If null, then the model’s default behavior
               will be executed. Default behaviour is controlled by the algorithm’s author.
     * @param string $file_content_type
     * @return Task, since all processing fit algorithms always end up in queue.
     */
    public function trainAlgorithm(
        $algorithm_id,
        $data,
        $version = null,
        $export_new_version = null,
        $file_content_type = "application/json"
    )
    {
        if (is_string($data)) {
            $request_files =
                    [
                        'features' => curl_file_create($data, $file_content_type, 'featuers.json'),
                    ];
        } else {
            $request_files = ['features' => ['fileName' => 'featuers.json', 'fileContent' => json_encode($data)]];
        }
        $request_data = ['export_new_version' => $export_new_version];
    
        if (! is_null($version)) {
            $request_data['version'] = $version;
        }
        $response = $this->http->POST("/algorithms/{$algorithm_id}/fit", $request_data, $request_files);

        return $this->mapResource('Task', $response);
    }

    /**
     * Convert the evaluate parameter to the correct format before sending the request.
     *
     * @param bool|array|string $evaluate parameter
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
     * Get all versions of the specified algorithm.
     *
     * @param $algorithmId
     * @return array|false|string
     */
    public function getAlgorithmVersions($algorithmId)
    {
        $response = $this->http->GET("/algorithms/{$algorithmId}/versions");
        if (is_array($response)) {
            foreach ($response as $key => $algorithm) {
                $response[$key]->algorithm_id = $algorithmId;
            }
        }

        return $this->mapCollection('Version', $response);
    }

    /**
     * Get a specific version for a specific algorithm.
     *
     * @param $algorithmId
     * @param $versionId
     * @return false|Resource|string
     */
    public function getAlgorithmVersion($algorithmId, $versionId)
    {
        $response = $this->http->GET("/algorithms/{$algorithmId}/versions/{$versionId}");
        if (is_object($response)) {
            $response->algorithm_id = $algorithmId;
        }

        return $this->mapResource('Version', $response);
    }

    /**
     * Downloads the detailed file.
     *
     * @param String $algorithmId
     * @param String $type describes the file type whether `input` or `output`
     * @param null | String $version specify the template of the version you would like to query.
     * @return bool
     */
    public function getTemplate(string $algorithmId, string $type = 'input', $version = null): bool
    {
        $response = $this->http->GET(
            "/algorithms/{$algorithmId}/template",
            ['type' => $type, 'version' => $version]
        );
        if ($this->http->getHttpCode() == 200) {
            // to download the file.
            header("Content-type: application/json");
            header("Content-Disposition: attachment; filename={$algorithmId}-{$type}-template.json");
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
     * @param null | string $version
     * @return bool
     */
    public function getGraph(string $algorithm_id, string $type = 'input', $version = null): bool
    {
        $response = $this->http->GET(
            "/algorithms/{$algorithm_id}/graph",
            ['type' => $type, 'version' => $version]
        );
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
     * Decrypt the received message from AI Core.
     *
     * @param string base64_encoded holds the encrypted message.
     * @param int Chunking by bytes to feed to the decryptor algorithm.
     * @return String decrypted message.
     * @throws Exception
     */
    public function RSADecrypt($encrypted_msg): string
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
