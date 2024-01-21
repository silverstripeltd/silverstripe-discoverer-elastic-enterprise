<?php

namespace SilverStripe\SearchElastic\Service;

use Elastic\EnterpriseSearch\AppSearch\Request\LogClickthrough;
use Elastic\EnterpriseSearch\AppSearch\Request\Search;
use Elastic\EnterpriseSearch\AppSearch\Schema\ClickParams;
use Elastic\EnterpriseSearch\Client;
use Exception;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Search\Analytics\AnalyticsData;
use SilverStripe\Search\Query\Query;
use SilverStripe\Search\Service\Results\Results;
use SilverStripe\Search\Service\SearchServiceAdaptor as SearchServiceAdaptorInterface;
use SilverStripe\SearchElastic\Processors\QueryParamsProcessor;
use SilverStripe\SearchElastic\Processors\ResultsProcessor;
use Throwable;

class SearchServiceAdaptor implements SearchServiceAdaptorInterface
{

    use Injectable;

    private ?Client $client = null;

    private ?LoggerInterface $logger = null;

    private static array $dependencies = [
        'client' => '%$' . Client::class,
        'logger' => '%$' . LoggerInterface::class . '.errorhandler',
    ];

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @throws Exception
     */
    public function search(Query $query, string $indexName): Results
    {
        // Instantiate our Results class with empty data. This will still be returned if there is an Exception during
        // communication with Elastic (so that the page doesn't seriously break)
        $results = Results::create($query);

        try {
            $params = QueryParamsProcessor::singleton()->getQueryParams($query);
            $engine = $this->environmentizeIndex($indexName);
            $request = new Search($engine, $params);
            $response = $this->client->appSearch()->search($request);

            ResultsProcessor::singleton()->getProcessedResults($results, $response->asArray());
            // If we got this far, then the request was a success
            $results->setSuccess(true);
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->logger->error(sprintf('Elastic error: %s', $e->getMessage()), ['elastic' => $e]);
            // Our request was not a success
            $results->setSuccess(false);
        } finally {
            return $results;
        }
    }

    public function processAnalytics(AnalyticsData $analyticsData): void
    {
        $query = $analyticsData->getQueryString();
        $documentId = $analyticsData->getDocumentId();
        $requestId = $analyticsData->getRequestId();
        $engineName = $analyticsData->getEngineName();

        try {
            $params = new ClickParams($query, $documentId);

            if ($requestId) {
                $params->request_id = $requestId;
            }

            $this->client->appSearch()->logClickthrough(new LogClickthrough($engineName, $params));
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->logger->error(sprintf('Elastic error: %s', $e->getMessage()), ['elastic' => $e]);
        }
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
