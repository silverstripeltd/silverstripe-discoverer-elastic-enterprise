<?php

namespace SilverStripe\SearchElastic\Service;

use Elastic\EnterpriseSearch\AppSearch\Request\LogClickthrough;
use Elastic\EnterpriseSearch\AppSearch\Request\Search;
use Elastic\EnterpriseSearch\AppSearch\Schema\ClickParams;
use Elastic\EnterpriseSearch\Client;
use Exception;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\Debug;
use SilverStripe\Search\Analytics\AnalyticsData;
use SilverStripe\Search\Query\Query;
use SilverStripe\Search\Service\Results\Results;
use SilverStripe\Search\Service\SearchServiceAdaptor as SearchServiceAdaptorInterface;
use SilverStripe\SearchElastic\Processors\QueryParamsProcessor;
use SilverStripe\SearchElastic\Processors\ResultsProcessor;

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

    /**
     * @throws Exception
     */
    public function search(Query $query, ?string $indexName = null): Results
    {
        $params = QueryParamsProcessor::getQueryParams($query);
        $engine = $this->environmentizeIndex($indexName);
        $request = new Search($engine, $params);
        $response = $this->client->appSearch()->search($request);

        return ResultsProcessor::getProcessedResults($query, $response);
    }

    public function processAnalytics(AnalyticsData $analyticsData): void
    {
        $query = $analyticsData->getQueryString();
        $documentId = $analyticsData->getDocumentId();
        $requestId = $analyticsData->getRequestId();
        $engineName = $analyticsData->getEngineName();

        $params = new ClickParams($query, $documentId);

        if ($requestId) {
            $params->request_id = $requestId;
        }

        $this->client->appSearch()->logClickthrough(new LogClickthrough($engineName, $params));
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
