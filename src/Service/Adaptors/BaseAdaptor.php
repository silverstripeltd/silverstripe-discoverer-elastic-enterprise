<?php

namespace SilverStripe\DiscovererElasticEnterprise\Service\Adaptors;

use Elastic\EnterpriseSearch\Client;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;

abstract class BaseAdaptor
{

    use Injectable;
    use Configurable;

    private ?Client $client = null;

    private ?LoggerInterface $logger = null;

    private static string $prefix_env_var = 'ENTERPRISE_SEARCH_ENGINE_PREFIX';

    private static array $dependencies = [
        'client' => '%$' . Client::class . '.searchClient',
        'logger' => '%$' . LoggerInterface::class . '.errorhandler',
    ];

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    protected function environmentizeIndex(string $indexName): string
    {
        $variant = Environment::getEnv($this->config()->get('prefix_env_var'));

        if ($variant) {
            return sprintf('%s-%s', $variant, $indexName);
        }

        return $indexName;
    }

}
