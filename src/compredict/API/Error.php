<?php
namespace Compredict\API;
/**
 * Base class for API exceptions. Used if failOnError is true.
 */
class Error extends \Exception
{
    public function __construct($response, $code)
    {
        $message = (isset($response->errors)) ? $response->errors[0] : $response->error;
        parent::__construct($message, $code);
    }
}