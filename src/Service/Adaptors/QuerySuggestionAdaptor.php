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

    public function process(Suggestion $suggestion, string $indexName): Suggestions
    {
        // Instantiate our Suggestions class with empty data. This will still be returned if there is an Exception
        // during communication with Elastic (so that the page doesn't seriously break)
        $suggestions = Suggestions::create();

        try {
            $engine = $this->environmentizeIndex($indexName);
            $params = SuggestionParamsProcessor::singleton()->getQueryParams($suggestion);
            $request = Injector::inst()->create(QuerySuggestion::class, $engine, $params);
            $response = $this->getClient()->appSearch()->querySuggestion($request);

            SuggestionsProcessor::singleton()->getProcessedSuggestions($suggestions, $response->asArray());
            // If we got this far, then the request was a success
            $suggestions->setSuccess(true);
        } catch (ClientErrorResponseException $e) {
            $errors = (string) $e->getResponse()->getBody();
            // Log the error without breaking the page
            $this->getLogger()->error(sprintf('Elastic error: %s', $errors), ['elastic' => $e]);
            // Our request was not a success
            $suggestions->setSuccess(false);
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->getLogger()->error(sprintf('Elastic error: %s', $e->getMessage()), ['elastic' => $e]);
            // Our request was not a success
            $suggestions->setSuccess(false);
        } finally {
            return $suggestions;
        }
    }

}
