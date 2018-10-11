COMPREDICT's AI CORE API Client
===============================

PHP client for connecting to the COMPREDICT V1 REST API.

To find out more, visit the official documentation website:
https://compredict.de

Requirements
------------

- PHP 7.0 or greater
- cUrl extension enabled

**To connect to the API with basic auth you need the following:**

- API Key taken from COMPREDICT's User Dashboard
- Username of the account.
- (Optional) Callback url to send the results

Installation
------------

Use the following Composer command to install the
API client from [the COMPREDICT vendor on Packagist](https://packagist.org/packages/compredict/ai-sdk):

~~~shell
 $ composer require compredict/ai-sdk
 $ composer update
~~~

Namespace
---------

All the examples below assume the `Compredict\API\Client` class is imported
into the scope with the following namespace declaration:

~~~php
use Compredict\API\Client as Compredict;
~~~

Configuration
-------------

To use the API client in your PHP code, ensure that you can access `Compredict\API`
in your autoload path (using Composer’s `vendor/autoload.php` hook is recommended).

Provide your credentials to the static configuration hook to prepare the API client
for connecting to a store on the Bigcommerce platform:

### Basic Auth
~~~php
$compredict_client = Compredict::getInstance(
    'Your-token',
    'Your-callback-url',
);
~~~

Accessing Algorithms (GET)
--------------------------

To list all the algorithms in a collection:

~~~php
$algorithms = $compredict_client->getAlgorithms();

foreach ($algorithms as $algorithm) {
    echo $algorithm->name;
    var_dump($algorithm->getTemplate());
}
~~~

To access a single algorithm:

~~~php
$algorithm = $compredict_client->getAlgorithm('ecolife');

echo $algorithm->name;
echo $algorithm->description;
~~~

Algorithm Prediction (POST)
-----------------------------

Some resources support creation of new items by posting to the collection. This
can be done by passing an array or stdClass object representing the new
resource to the global create method:

~~~php
$X_test = array(
    "feature_1" => [1, 2, 3, 4], 
    "feature_2" => [2, 3, 4, 5]
);

$algorithm = $compredict_client->getAlgorithm('algorithm_id');
$result = $algorithm->predict($X_test);
~~~

Depending on the algorithm's computation requirement, the result can be:

- **Task**: holds a job id of the task that the user can query later to get the results.
- **Prediction**: contains the result of the algorithm + evaluation

You can identify when the algorithm dispatches the processing to queue 
or send the results instantly by:

~~~php
echo $algorithm->getResponseTime();
~~~

or dynamically:

~~~php
$result = $algorithm->predict(X_test, evaluate=True);

if($result instanceof Compredict\API\Resources\Task){
    echo $result->getCurrentStatus();
    // wait some time
    echo $result->getLatestUpdate(); // check Compredict for the results.
}
~~~

If you set up ``callback_url`` then the results will be POSTed automatically to you once the
calculation is finished.

Handling Errors And Timeouts
----------------------------

For whatever reason, the HTTP requests at the heart of the API may not always
succeed.

Every method will return false if an error occurred, and you should always
check for this before acting on the results of the method call.

In some cases, you may also need to check the reason why the request failed.
This would most often be when you tried to save some data that did not validate
correctly.

~~~php
$algorithms = $compredict_client->getAlgorithms();

if (!$algorithms) {
    $error = $compredict_client->getLastError();
    echo $error->code;
    echo $error->message;
}
~~~

Returning false on errors, and using error objects to provide context is good
for writing quick scripts but is not the most robust solution for larger and
more long-term applications.

An alternative approach to error handling is to configure the API client to
throw exceptions when errors occur. Bear in mind, that if you do this, you will
need to catch and handle the exception in code yourself. The exception throwing
behavior of the client is controlled using the failOnError method:

~~~php
$compredict_client->failOnError();

try {
    $orders = $compredict_client->getAlgorithms();

} catch(Compredict\API\Error $error) {
    echo $error->getCode();
    echo $error->getMessage();
}
~~~

The exceptions thrown are subclasses of Error, representing
client errors and server errors. The API documentation for response codes
contains a list of all the possible error conditions the client may encounter.


Verifying SSL certificates
--------------------------

By default, the client will attempt to verify the SSL certificate used by the
COMPREDICT AI Core. In cases where this is undesirable, or where an unsigned
certificate is being used, you can turn off this behavior using the verifyPeer
switch, which will disable certificate checking on all subsequent requests:

~~~php
$compredict_client->verifyPeer(false);
~~~