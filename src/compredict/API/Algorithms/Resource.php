<?php

namespace Compredict\API\Algorithms;

class Resource
{

    /**
     * @var \stdClass
     */
    protected $fields;

    /**
     * @var \CompredictAICore\Api\Client
     */
    protected $client;

    public function __construct($object = false, $client = null)
    {
        if (is_array($object)) {
            $object = (isset($object[0])) ? $object[0] : false;
        }
        $this->fields = ($object) ? $object : new \stdClass;
        $this->client = $client;
    }

    public function __get($field)
    {
        // then, if a method exists for the specified field and the field we should actually be examining
        // has a value, call the method instead
        if (method_exists($this, $field) && isset($this->fields->$field)) {
            return $this->$field();
        }
        // otherwise, just return the field directly (or null)
        return (isset($this->fields->$field)) ? $this->fields->$field : null;
    }

    public function __set($field, $value)
    {
        if ($field == 'fields') {
            $this->fields = $value;
        } else {
            $this->fields->$field = $value;
        }

    }

    public function __isset($field)
    {
        return (isset($this->fields->$field));
    }

}
