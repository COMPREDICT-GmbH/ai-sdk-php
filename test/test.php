<?php
namespace Compredict\Test;
include 'autoloader.php';

use \Compredict\Api\Resources\Task as Task;
use \Compredict\Api\Client as Compredict;
use \Dotenv\Dotenv;


// load the settings
$dotenv = new Dotenv(__DIR__ . '\..');
$dotenv->load();

$token= getenv("COMPREDICT_AI_CORE_KEY", "");
$user= getenv("COMPREDICT_AI_CORE_USER", "");
$callback_url= getenv("COMPREDICT_AI_CORE_CALLBACK", null);
$fail_on_error= getenv("COMPREDICT_AI_CORE_FAIL_ON_ERROR", true);
$ppk_path= getenv("COMPREDICT_AI_CORE_PPK", null);
$passphrase= getenv("COMPREDICT_AI_CORE_PASSPHRASE", "");


// Create compredict client and set the necessary options.
$client = Compredict::getInstance($token, $callback_url, $ppk_path, $passphrase);
$client->failOnError(false);


// Calling an algorithm and test it.
$test_data = file_get_contents("test_observer.json");

$algo = $client->getAlgorithm('observer');

if($algo == False){
    var_dump($client->getLastError());
    die();
}

if($results = $algo->predict(json_decode($test_data, true), $evaluate=false, $encrypt=true)){
    var_dump($results);
    if($results instanceof Task){
        echo "It is a task<br>";
        while($results->getCurrentStatus() != Task::STATUS_FINISHED){
            sleep(3);
            $results->update();
            echo "Checking for update... the new status is: " . $results->getCurrentStatus() . '<br>';
        }
    }
    var_dump($results->predictions);
} else {
    var_dump($client->getLastError());
}

