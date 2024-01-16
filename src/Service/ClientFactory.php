<?php

namespace SilverStripe\SearchElastic\Service;

use Elastic\EnterpriseSearch\Client;
use Exception;
use SilverStripe\Core\Injector\Factory;

class ClientFactory implements Factory
{

    /**
     * @throws Exception
     */
    public function create(mixed $service, array $params = []) // phpcs:ignore SlevomatCodingStandard.TypeHints
    {
        $host = $params['host'] ?? null;
        $token = $params['token'] ?? null;
        $httpClient = $params['http_client'] ?? null;

        if (!$host || !$token) {
            throw new Exception('ENTERPRISE_SEARCH_ENDPOINT and ENTERPRISE_SEARCH_API_KEY are required');
        }

        if (!$httpClient) {
            throw new Exception('http_client required');
        }

        $config = [
            'host' => $host,
            'app-search' => [
                'token' => $token,
            ],
            'enterprise-search' => [
                'token' => $token,
            ],
        ];

        return new Client($config);
    }

}
