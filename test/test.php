<?php
namespace Compredict\Test;

include 'includer.php';

$dotenv = new \Dotenv\Dotenv(__DIR__ . '\..');
$dotenv->load();

$token= getenv("COMPREDICT_AI_CORE_KEY", "");
$user= getenv("COMPREDICT_AI_CORE_USER", "");
$callback_url= getenv("COMPREDICT_AI_CORE_CALLBACK", null);
$fail_on_error= getenv("COMPREDICT_AI_CORE_FAIL_ON_ERROR", true);

$client = \Compredict\API\Client::getInstance($token, $callback_url);
$client->failOnError(True);

$test_data = file_get_contents("test_observer.json");

$algo = $client->getAlgorithm('observer');

if($prediction = $algo->predict(json_decode($test_data, true), $evaluate=false)){
    var_dump($prediction);
} else {
    var_dump($client->getLastError());
}

//sleep(15);

// if($algo->last_result instanceof \CompredictAICore\Api\Resources\Task){
//     echo "It is a task indeed";
//     $task = $algo->last_result;
//     $task->getLatestUpdate();
//     var_dump($task->predictions);
// }