<?php

namespace Compredict\API\Algorithms;

/**
 * Raised when a client error (400+) is returned from the API.
 */
class ClientError extends Error
{
    public function __toString()
    {
        return "Client Error ({$this->code}): " . $this->message;
    }
}
