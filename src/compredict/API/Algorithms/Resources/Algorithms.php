<?php

namespace Compredict\API\Algorithms\Resources;

class Algorithms
{

    protected $algorithms;

    public function __construct($object = false)
    {
        $this->algorithms = $object;
    }

    public function __get($field)
    {
        if ($this->algorithms === false) {
            return false;
        }

        $index = array_search($field, array_column($this->algorithms, 'id'));
        return ($index === false) ? null : new Algorithm($this->algorithms[$index]);
    }

    public function __toString()
    {
        return "[" . implode(array_column($this->algorithms, 'id'), ",") . "]";
    }
}
