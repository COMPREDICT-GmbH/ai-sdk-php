<?php

namespace Compredict\Tests;

use Compredict\API\Algorithms\Client;
use Compredict\API\Algorithms\Resources\Algorithm;
use Compredict\API\Algorithms\Resources\Algorithms;
use Dotenv\Dotenv;
use Exception;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public $client;

    public function setUp(): void
    {
        if (file_exists(dirname(__DIR__) . '/.env')) {
            (new Dotenv(dirname(__DIR__), '.env'))->load();
        }

        $this->client = new Client(getenv('COMPREDICT_AI_CORE_KEY'));
        $this->client->setURL('https://b.aic.compredict.de/api/v1');
    }

    /** @test */
    public function it_will_return_a_compredict_client()
    {
        $this->assertInstanceOf(Client::class, $this->client);
    }

    /** @test */
    public function it_will_require_a_token_with_40_character_length()
    {
        $this->expectException(\Exception::class);

        try {
            new Client('not-a-40-character-token');
        } catch (Exception $e) {
            $this->assertEquals('A 40 character API Key must be provided', $e->getMessage());

            throw $e;
        }
    }

    /** @test */
    public function it_will_return_an_array_of_algorithms()
    {
        $algorithms = $this->client->getAlgorithms();

        $this->assertIsArray($algorithms->algorithms);
        $this->assertInstanceOf(Algorithms::class, $algorithms);
    }

    /** @test */
    public function it_will_return_an_algorithm_based_on_id()
    {
        $observerAlgorithm = $this->client->getAlgorithm('observer');

        $this->assertEquals('observer', $observerAlgorithm->id);
        $this->assertInstanceOf(Algorithm::class, $observerAlgorithm);
    }
}
