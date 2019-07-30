<?php
namespace Compredict\Test;

include 'autoloader.php';

use \Compredict\Api\Algorithms\Client as Compredict;
use \Dotenv\Dotenv;

// load the settings
$dotenv = new Dotenv(__DIR__ . '\..');
$dotenv->load();

$token = getenv("COMPREDICT_AI_CORE_KEY", "");
$user = getenv("COMPREDICT_AI_CORE_USER", "");
$callback_url = getenv("COMPREDICT_AI_CORE_CALLBACK", null);
$fail_on_error = getenv("COMPREDICT_AI_CORE_FAIL_ON_ERROR", true);
$ppk_path = getenv("COMPREDICT_AI_CORE_PPK", null);
$passphrase = getenv("COMPREDICT_AI_CORE_PASSPHRASE", "");

// Create compredict client and set the necessary options.
$client = Compredict::getInstance($token, null, $fail_on_error, $passphrase);
$client->failOnError(false);

// Calling an algorithm and test it.
$test_data = file_get_contents("test_observer.json");
$algos = $client->getAlgorithms();

if ($algos == false) {
    var_dump($client->getLastError());
    die();
}

$evaluate = [];
$evaluate['rainflow-counting'] = [];
$evaluate['rainflow-counting']["hysteresis"] = 0.2;
$evaluate['rainflow-counting']["N"] = 2;

if ($results = $algos->observer->predict(json_decode($test_data, true), $evaluate = $evaluate, $encrypt = false)) {
    if ($results instanceof Task) {
        echo "It is a task<br>";
        while ($results->getCurrentStatus() != Task::STATUS_FINISHED) {
            sleep(3);
            $results->update();
            echo "Checking for update... the new status is: " . $results->getCurrentStatus() . '<br>';
        }
    }
    var_dump($results->evaluations);
} else {
    var_dump($client->getLastError());
}
