<?php


namespace Compredict\Tests;

use Mockery;
use PHPUnit\Framework\TestCase;
use Compredict\API\Algorithms\Client;
use Compredict\API\Algorithms\Resources\Task;

class AlgorithmTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /** @test */
    public function it_will_predict_a_result()
    {
        $mock = Mockery::mock(Client::class);
        $mock->shouldReceive('getPrediction')
            ->withArgs(['algorithm-id', 'fake-data'])
            ->once()
            ->andReturn(new Task());

        $this->assertInstanceOf(Task::class, $mock->getPrediction('algorithm-id', 'fake-data'));
    }
}
