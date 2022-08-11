<?php

namespace Compredict\Tests;

use Compredict\API\Algorithms\Client;
use Compredict\API\Algorithms\Request;
use Compredict\API\Algorithms\Resources\Algorithm;
use Compredict\API\Algorithms\Resources\Algorithms;
use Compredict\API\Algorithms\Resources\Prediction;
use Compredict\API\Algorithms\Resources\Task;
use Compredict\API\Algorithms\Resources\Version;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public $client;

    public const RESPONSES_PATH = __DIR__ . '/responses/';

    public function setUp(): void
    {
        $this->httpMock = $this->createMock(Request::class);

        $fakeToken = "xXzzczhAiF2kdK1sv8bD4Wv2aQJfV4PSxMXCGOjj";

        $this->client = new Client($fakeToken, null, null, "", $this->httpMock);
    }
    /**
     * @test
     */
    public function testGetAlgorithm()
    {
        $bodyResponse = json_decode(file_get_contents(self::RESPONSES_PATH . 'algorithm.json'));
        
        $this->httpMock->method('GET')->willReturn($bodyResponse);

        $retrievedAlgorithm = $this->client->getAlgorithm("dummy-test-model");

        $expectedName = "Dummy Test Model";

        $this->assertInstanceOf(Algorithm::class, $retrievedAlgorithm);
        $this->assertSame($expectedName, $retrievedAlgorithm->name);
    }

    /**
     * @test
     */
    public function testGetAlgorithms()
    {
        $bodyResponse = json_decode(file_get_contents(self::RESPONSES_PATH . 'algorithms.json'));

        $this->httpMock->method('GET')->willReturn($bodyResponse);

        $retrievedAlgorithms = $this->client->getAlgorithms();

        $expectedAlgorithm = $retrievedAlgorithms->__get('another-dummy-test');

        $this->assertInstanceOf(Algorithms::class, $retrievedAlgorithms);
        $this->assertSame($expectedAlgorithm->name, 'Another Dummy Test');
    }

    /**
     * @test
     */
    public function testGetTaskResult()
    {
        $bodyResponse = json_decode(file_get_contents(self::RESPONSES_PATH . 'taskResult.json'));

        $this->httpMock->method('GET')->willReturn($bodyResponse);

        $jobId = 'c751dfb7-56cc-4504-8fb9-19b0a79d9197';

        $retrievedTaskResult = $this->client->getTaskResult($jobId);

        $this->assertInstanceOf(Task::class, $retrievedTaskResult);
        $this->assertSame($retrievedTaskResult->status, 'Finished');
        $this->assertObjectHasAttribute("Range#mileage", $retrievedTaskResult->monitors);
    }

    /**
     * @test
     */
    public function testCancelTask()
    {
        $bodyResponse = json_decode(file_get_contents(self::RESPONSES_PATH . 'deletedTask.json'));

        $this->httpMock->method('DELETE')->willReturn($bodyResponse);

        $jobId = 'c751dfb7-56cc-4504-8fb9-19b0a79d9197';

        $deletedTask = $this->client->cancelTask($jobId);

        $this->assertInstanceOf(Task::class, $deletedTask);
        $this->assertSame(true, $deletedTask->is_canceled);
    }

    /**
     * @test
     */
    public function testGetPrediction()
    {
        $bodyResponse = json_decode(file_get_contents(self::RESPONSES_PATH . 'predictionResult.json'));

        $this->httpMock->method('POST')->willReturn($bodyResponse);

        $algorithmId = 'dummy-test-model';
        $data = file_get_contents(self::RESPONSES_PATH . 'features.json');

        $prediction = $this->client->getPrediction($algorithmId, $data);

        $this->assertInstanceOf(Prediction::class, $prediction);
        $this->assertSame(false, $prediction->is_encrypted);
        $this->assertObjectHasAttribute("Range#mileage", $prediction->monitors);
    }
    
    /**
     * @test
     */
    public function testGetPredictionSentToQueue()
    {
        $bodyResponse = json_decode(file_get_contents(self::RESPONSES_PATH . 'jobId.json'));
        $this->httpMock->method('POST')->willReturn($bodyResponse);

        $algorithmId = 'dummy-test-model';
        $data = file_get_contents(self::RESPONSES_PATH . 'features.json');

        $prediction = $this->client->getPrediction($algorithmId, $data);

        $this->assertInstanceOf(Task::class, $prediction);
    }

    /**
     * @test
     */
    public function testTrainAlgorithm()
    {
        $bodyResponse = json_decode(file_get_contents(self::RESPONSES_PATH . 'jobId.json'));
        $this->httpMock->method('POST')->willReturn($bodyResponse);

        $algorithmId = 'dummy-test-model';
        $data = file_get_contents(self::RESPONSES_PATH . 'features.json');

        $task = $this->client->trainAlgorithm($algorithmId, $data);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertSame("8afff4e8-8c4e-4412-b6db-cfd295503c8f", $task->job_id);
    }

    /**
     * @test
     */
    public function testTrainAlgorithmWithError()
    {
        $this->httpMock->method('POST')->willReturn(false);

        $algorithmId = 'dummy-test-model';
        $data = file_get_contents(self::RESPONSES_PATH . 'features.json');

        $jobId = $this->client->trainAlgorithm($algorithmId, $data);

        $this->assertSame(false, $jobId);
    }

    /**
     * @test
     */
    public function testTrainAlgorithmWithRetrievingLastError()
    {
        $bodyResponse = json_decode(file_get_contents(self::RESPONSES_PATH . 'unsuccessfulFit.json'));
        $this->httpMock->method('getLastError')->willReturn($bodyResponse);
        $this->httpMock->method('POST')->willReturn(false);

        $algorithmId = 'dummy-test-model';
        $data = file_get_contents(self::RESPONSES_PATH . 'features.json');

        $jobId = $this->client->trainAlgorithm($algorithmId, $data);
        $lastError = $this->client->getLastError();

        $this->assertSame(false, $jobId);
        $this->assertSame(false, $lastError->status);
    }

    /**
     * @test
     */
    public function testGetAlgorithmVersions()
    {
        $bodyResponse = json_decode(file_get_contents(self::RESPONSES_PATH . 'algorithmVersions.json'));
        $this->httpMock->method('GET')->willReturn($bodyResponse);

        $algorithmId = 'dummy-test-model';

        $versions = $this->client->getAlgorithmVersions($algorithmId);

        $this->assertInstanceOf(Version::class, $versions[0]);
        $this->assertSame("1.1.1", $versions[0]->version);
    }

    /**
     * @test
     */
    public function testGetAlgorithmVersion()
    {
        $bodyResponse = json_decode(file_get_contents(self::RESPONSES_PATH . 'algorithmVersion.json'));
        $this->httpMock->method('GET')->willReturn($bodyResponse);

        $algorithmId = 'dummy-test-model';
        $versionId = "1.1.1";

        $version = $this->client->getAlgorithmVersion($algorithmId, $versionId);

        $expected = "Request will be escalated to queue if number of samples are >= 0.";

        $this->assertInstanceOf(Version::class, $version);
        $this->assertSame($expected, $version->results);
    }

    /**
     * @test
     */
    public function testGetTemplate()
    {
        $this->httpMock->expects($this->once())->method('getHttpCode');

        $algorithmId = "dummy-model";

        $result = $this->client->getTemplate($algorithmId);

        $this->assertSame(false, $result);
    }

    /**
     * @test
     */
    public function testGetGraph()
    {
        $this->httpMock->expects($this->once())->method('getHttpCode');

        $algorithmId = "dummy-model";

        $result = $this->client->getGraph($algorithmId);

        $this->assertSame(false, $result);
    }
}
