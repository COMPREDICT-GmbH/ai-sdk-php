<?php

namespace CompredictAICore\Test;

include "../src/CompredictAICore/Api/SingletonTrait.php";
include "../src/CompredictAICore/Api/Error.php";
include "../src/CompredictAICore/Api/ClientError.php";
include "../src/CompredictAICore/Api/NetworkError.php";
include "../src/CompredictAICore/Api/ServerError.php";
include "../src/CompredictAICore/Api/Client.php";
include "../src/CompredictAICore/Api/Request.php";
include "../src/CompredictAICore/Api/Resource.php";
include "../src/CompredictAICore/Api/Resources/Algorithm.php";
include "../src/CompredictAICore/Api/Resources/Prediction.php";
include "../src/CompredictAICore/Api/Resources/Task.php";
include "../src/CompredictAICore/Api/Resources/Evaluation.php";

$token = "10d342c2d46031f540442b72962a47613033642b";
$callback_url = 'http://localhost/sdk/callback.php';

$client = \CompredictAICore\Api\Client::getInstance($token, $callback_url);
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