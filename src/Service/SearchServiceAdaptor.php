<?php

namespace SilverStripe\SearchElastic\Service;

use Elastic\EnterpriseSearch\AppSearch\Request\Search;
use Elastic\EnterpriseSearch\Client;
use SilverStripe\Core\Environment;
use SilverStripe\Search\Analytics\AnalyticsData;
use SilverStripe\Search\Query\Query;
use SilverStripe\Search\Service\Results\Results;
use SilverStripe\Search\Service\SearchServiceAdaptor as SearchServiceAdaptorInterface;
use SilverStripe\SearchElastic\Helpers\QueryParamsProcessor;
use SilverStripe\SearchElastic\Helpers\ResultsProcessor;

class SearchServiceAdaptor implements SearchServiceAdaptorInterface
{

    private ?Client $client = null;

    private static array $dependencies = [
        'client' => '%$' . Client::class,
    ];

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function search(Query $query, ?string $indexName = null): Results
    {
        $params = QueryParamsProcessor::getQueryParams($query);
        $engine = $this->environmentizeIndex($indexName);
        $request = new Search($engine, $params);
        $response = $this->client->appSearch()->search($request);

        $results = ResultsProcessor::processResponse($query, $response);

        return $results;
    }

    public function processAnalytics(AnalyticsData $analyticsData): void
    {
        // TODO: Implement logClickThrough() method.
    }

    private function environmentizeIndex(string $indexName): string
    {
        $variant = Environment::getEnv('ENTERPRISE_SEARCH_ENGINE_PREFIX');

        if ($variant) {
            return sprintf('%s-%s', $variant, $indexName);
        }

        return $indexName;
    }

}
