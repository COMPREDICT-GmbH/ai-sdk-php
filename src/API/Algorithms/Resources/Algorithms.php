<?php

namespace Compredict\API\Algorithms\Resources;

class Algorithms
{
    public $algorithms;

    public function __construct($object = false, $client = null)
    {
        $this->algorithms = $object;
        $this->client = $client;
    }

    public function __get($field)
    {
        if ($this->algorithms === false) {
            return false;
        }

        $index = array_search($field, array_column($this->algorithms, 'id'));

        return ($index === false) ? null : new Algorithm($this->algorithms[$index], $this->client);
    }

    public function __toString()
    {
        return "[" . implode(array_column($this->algorithms, 'id'), ",") . "]";
    }
}
