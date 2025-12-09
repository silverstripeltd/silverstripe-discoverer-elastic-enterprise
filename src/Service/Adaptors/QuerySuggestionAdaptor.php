<?php

namespace SilverStripe\DiscovererElasticEnterprise\Service\Adaptors;

use Elastic\EnterpriseSearch\AppSearch\Request\QuerySuggestion;
use Elastic\EnterpriseSearch\Exception\ClientErrorResponseException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Discoverer\Query\Suggestion;
use SilverStripe\Discoverer\Service\Interfaces\QuerySuggestionAdaptor as QuerySuggestionAdaptorInterface;
use SilverStripe\Discoverer\Service\Results\Suggestions;
use SilverStripe\DiscovererElasticEnterprise\Processors\SuggestionParamsProcessor;
use SilverStripe\DiscovererElasticEnterprise\Processors\SuggestionsProcessor;
use Throwable;

class QuerySuggestionAdaptor extends BaseAdaptor implements QuerySuggestionAdaptorInterface
{

    /**
     * This method will return an empty Suggestions object in case of an exception during communication with Elastic,
     * to prevent the page from breaking.
     */
    public function process(Suggestion $suggestion, string $indexSuffix): Suggestions
    {
        try {
            $engine = $this->environmentizeIndex($indexSuffix);
            $params = SuggestionParamsProcessor::singleton()->getQueryParams($suggestion);
            $request = Injector::inst()->create(QuerySuggestion::class, $engine, $params);
            $response = $this->getClient()->appSearch()->querySuggestion($request);

            $suggestions = Suggestions::create(200);
            SuggestionsProcessor::singleton()->getProcessedSuggestions($suggestions, $response->asArray());

            // If we got this far, then the request was a success
            return $suggestions;
        } catch (ClientErrorResponseException $e) {
            $errors = (string) $e->getResponse()->getBody();
            // Log the error without breaking the page
            $this->getLogger()->error(sprintf('Elastic error: %s', $errors), ['elastic' => $e]);

            // Our request was not a success
            return Suggestions::create($e->getCode());
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->getLogger()->error(sprintf('Elastic error: %s', $e->getMessage()), ['elastic' => $e]);

            // Our request was not a success
            return Suggestions::create($e->getCode());
        }
    }

}
