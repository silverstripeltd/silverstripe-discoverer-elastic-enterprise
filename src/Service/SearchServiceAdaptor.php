<?php

namespace SilverStripe\DiscovererElasticEnterprise\Service;

use Elastic\EnterpriseSearch\AppSearch\Request\LogClickthrough;
use Elastic\EnterpriseSearch\AppSearch\Request\QuerySuggestion;
use Elastic\EnterpriseSearch\AppSearch\Request\Search;
use Elastic\EnterpriseSearch\AppSearch\Schema\ClickParams;
use Elastic\EnterpriseSearch\Client;
use Elastic\EnterpriseSearch\Exception\ClientErrorResponseException;
use Exception;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Discoverer\Query\Suggestion;
use SilverStripe\Discoverer\Service\Results\Results;
use SilverStripe\Discoverer\Service\Results\Suggestions;
use SilverStripe\Discoverer\Service\SearchServiceAdaptor as SearchServiceAdaptorInterface;
use SilverStripe\DiscovererElasticEnterprise\Processors\QueryParamsProcessor;
use SilverStripe\DiscovererElasticEnterprise\Processors\ResultsProcessor;
use SilverStripe\DiscovererElasticEnterprise\Processors\SuggestionParamsProcessor;
use SilverStripe\DiscovererElasticEnterprise\Processors\SuggestionsProcessor;
use Throwable;

class SearchServiceAdaptor implements SearchServiceAdaptorInterface
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
            $request = Injector::inst()->create(Search::class, $engine, $params);
            $response = $this->client->appSearch()->search($request);

            ResultsProcessor::singleton()->getProcessedResults($results, $response->asArray());
            // If we got this far, then the request was a success
            $results->setSuccess(true);
        } catch (ClientErrorResponseException $e) {
            $errors = (string) $e->getResponse()->getBody();
            // Log the error without breaking the page
            $this->logger->error(sprintf('Elastic error: %s', $errors), ['elastic' => $e]);
            // Our request was not a success
            $results->setSuccess(false);
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->logger->error(sprintf('Elastic error: %s', $e->getMessage()), ['elastic' => $e]);
            // Our request was not a success
            $results->setSuccess(false);
        } finally {
            return $results;
        }
    }

    public function querySuggestion(Suggestion $suggestion, string $indexName): Suggestions
    {
        // Instantiate our Suggestions class with empty data. This will still be returned if there is an Exception
        // during communication with Elastic (so that the page doesn't seriously break)
        $suggestions = Suggestions::create();

        try {
            $engine = $this->environmentizeIndex($indexName);
            $params = SuggestionParamsProcessor::singleton()->getQueryParams($suggestion);
            $request = Injector::inst()->create(QuerySuggestion::class, $engine, $params);
            $response = $this->client->appSearch()->querySuggestion($request);

            SuggestionsProcessor::singleton()->getProcessedSuggestions($suggestions, $response->asArray());
            // If we got this far, then the request was a success
            $suggestions->setSuccess(true);
        } catch (ClientErrorResponseException $e) {
            $errors = (string) $e->getResponse()->getBody();
            // Log the error without breaking the page
            $this->logger->error(sprintf('Elastic error: %s', $errors), ['elastic' => $e]);
            // Our request was not a success
            $suggestions->setSuccess(false);
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->logger->error(sprintf('Elastic error: %s', $e->getMessage()), ['elastic' => $e]);
            // Our request was not a success
            $suggestions->setSuccess(false);
        } finally {
            return $suggestions;
        }
    }

    public function processAnalytics(AnalyticsData $analyticsData): void
    {
        $query = $analyticsData->getQueryString();
        $documentId = $analyticsData->getDocumentId();
        $requestId = $analyticsData->getRequestId();
        $engineName = $analyticsData->getEngineName();

        try {
            $params = Injector::inst()->create(ClickParams::class, $query, $documentId);

            if ($requestId) {
                $params->request_id = $requestId;
            }

            $clickThrough = Injector::inst()->create(LogClickthrough::class, $engineName, $params);
            $this->client->appSearch()->logClickthrough($clickThrough);
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->logger->error(sprintf('Elastic error: %s', $e->getMessage()), ['elastic' => $e]);
        }
    }

    private function environmentizeIndex(string $indexName): string
    {
        $variant = Environment::getEnv($this->config()->get('prefix_env_var'));

        if ($variant) {
            return sprintf('%s-%s', $variant, $indexName);
        }

        return $indexName;
    }

}
