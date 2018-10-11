<?php

namespace Compredict\API;

use \Exception as Exception;

/**
 * Singleton patter in php
 **/
trait SingletonTrait {
 protected static $inst = null;

  /**
   * call this method to get instance
   **/
  public static function getInstance($token=null, $callback_url=null){
    if (static::$inst === null){
     if($token == null)
      throw new Exception("Token must be provided");
    static::$inst = new static($token, $callback_url);
    }
    return static::$inst;
  }

    /**
     * Make clone magic method protected, so nobody can clone instance.
     */
    protected function __clone() {}

    /**
     * Make sleep magic method protected, so nobody can serialize instance.
     */
    protected function __sleep() {}

    /**
     * Make wakeup magic method protected, so nobody can unserialize instance.
     */
    protected function __wakeup() {}
  }