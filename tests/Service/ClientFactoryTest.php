<?php

namespace SilverStripe\SearchElastic\Tests\Service;

use Elastic\EnterpriseSearch\Client;
use GuzzleHttp\Client as GuzzleClient;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Search\Service\SearchService;
use SilverStripe\SearchElastic\Service\ClientFactory;

class ClientFactoryTest extends SapphireTest
{

    public function testCreate(): void
    {
        $clientFactory = new ClientFactory();
        $client = $clientFactory->create(
            SearchService::class,
            [
                'host' => 'abc123',
                'token' => 'abc123',
                'http_client' => new GuzzleClient(),
            ]
        );

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testCreateMissingEnvVars(): void
    {
        $this->expectExceptionMessage(
            'Required ENV vars missing: ENTERPRISE_SEARCH_ENDPOINT, ENTERPRISE_SEARCH_API_KEY'
        );

        $clientFactory = new ClientFactory();
        // Expect this to throw our Exception as no params have been passed
        $clientFactory->create(SearchService::class);
    }

    public function testCreateMissingClient(): void
    {
        $this->expectExceptionMessage('http_client required');

        $clientFactory = new ClientFactory();
        // Expect this to throw our Exception as no client was provided
        $clientFactory->create(
            SearchService::class,
            [
                'host' => 'abc123',
                'token' => 'abc123',
            ]
        );
    }

}
