<?php

namespace SilverStripe\DiscovererElasticEnterprise\Service\Adaptors;

use Elastic\EnterpriseSearch\AppSearch\Request\Search;
use Elastic\EnterpriseSearch\Exception\ClientErrorResponseException;
use Exception;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Discoverer\Service\Interfaces\SearchAdaptor as SearchAdaptorInterface;
use SilverStripe\Discoverer\Service\Results\Results;
use SilverStripe\DiscovererElasticEnterprise\Processors\QueryParamsProcessor;
use SilverStripe\DiscovererElasticEnterprise\Processors\ResultsProcessor;
use Throwable;

class SearchAdaptor extends BaseAdaptor implements SearchAdaptorInterface
{

    /**
     * @throws Exception
     */
    public function process(Query $query, string $indexName): Results
    {
        // Instantiate our Results class with empty data. This will still be returned if there is an Exception during
        // communication with Elastic (so that the page doesn't seriously break)
        $results = Results::create($query);

        try {
            $params = QueryParamsProcessor::singleton()->getQueryParams($query);
            $engine = $this->environmentizeIndex($indexName);
            $request = Injector::inst()->create(Search::class, $engine, $params);
            $response = $this->getClient()->appSearch()->search($request);

            ResultsProcessor::singleton()->getProcessedResults($results, $response->asArray());
            // If we got this far, then the request was a success
            $results->setSuccess(true);
        } catch (ClientErrorResponseException $e) {
            $errors = (string) $e->getResponse()->getBody();
            // Log the error without breaking the page
            $this->getLogger()->error(sprintf('Elastic error: %s', $errors), ['elastic' => $e]);
            // Our request was not a success
            $results->setSuccess(false);
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->getLogger()->error(sprintf('Elastic error: %s', $e->getMessage()), ['elastic' => $e]);
            // Our request was not a success
            $results->setSuccess(false);
        } finally {
            return $results;
        }
    }

}
