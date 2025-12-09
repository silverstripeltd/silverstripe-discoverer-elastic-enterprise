<?php

namespace SilverStripe\DiscovererElasticEnterprise\Service\Adaptors;

use Elastic\EnterpriseSearch\AppSearch\Request\Search;
use Elastic\EnterpriseSearch\Exception\ClientErrorResponseException;
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
     * This method will return a Results object with empty data in case of an
     * exception during communication with Elastic, to prevent the page from breaking.
     */
    public function process(Query $query, string $indexSuffix): Results
    {
        try {
            $params = QueryParamsProcessor::singleton()->getQueryParams($query);
            $engine = $this->environmentizeIndex($indexSuffix);
            $request = Injector::inst()->create(Search::class, $engine, $params);
            $response = $this->getClient()->appSearch()->search($request);

            $results = Results::create(200, $query);
            ResultsProcessor::singleton()->getProcessedResults($results, $response->asArray());

            // If we got this far, then the request was a success
            return $results;
        } catch (ClientErrorResponseException $e) {
            $errors = (string) $e->getResponse()->getBody();
            // Log the error without breaking the page
            $this->getLogger()->error(sprintf('Elastic error: %s', $errors), ['elastic' => $e]);

            // Our request was not a success
            return Results::create($e->getCode(), $query);
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->getLogger()->error(sprintf('Elastic error: %s', $e->getMessage()), ['elastic' => $e]);

            // Our request was not a success
            return Results::create($e->getCode(), $query);
        }
    }

}
