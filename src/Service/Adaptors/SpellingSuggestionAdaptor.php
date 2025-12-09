<?php

namespace SilverStripe\DiscovererElasticEnterprise\Service\Adaptors;

use Elastic\EnterpriseSearch\Exception\ClientErrorResponseException;
use Elastic\EnterpriseSearch\Response\Response;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Discoverer\Query\Suggestion;
use SilverStripe\Discoverer\Service\Interfaces\SpellingSuggestionAdaptor as SpellingSuggestionAdaptorInterface;
use SilverStripe\Discoverer\Service\Results\Suggestions;
use SilverStripe\DiscovererElasticEnterprise\Processors\SpellingParamsProcessor;
use SilverStripe\DiscovererElasticEnterprise\Processors\SpellingProcessor;
use SilverStripe\DiscovererElasticEnterprise\Service\Requests\ElasticsearchSearchRequest;
use Throwable;

class SpellingSuggestionAdaptor extends BaseAdaptor implements SpellingSuggestionAdaptorInterface
{

    /**
     * This method will return a Suggestions object with empty data in case of an
     * exception during communication with Elastic, to prevent the page from breaking.
     */
    public function process(Suggestion $suggestion, string $indexSuffix): Suggestions
    {
        try {
            $engine = $this->environmentizeIndex($indexSuffix);
            $params = SpellingParamsProcessor::singleton()->getQueryParams($suggestion);
            $request = ElasticsearchSearchRequest::create($engine, $params);

            $transportResponse = $this->getClient()->appSearch()->getTransport()->sendRequest($request->getRequest());
            $response = Injector::inst()->create(Response::class, $transportResponse);

            $suggestions = Suggestions::create(200);
            SpellingProcessor::singleton()->getProcessedSuggestions($suggestions, $response->asArray());

            // If we got this far, then the request was a success
            return $suggestions;
        } catch (ClientErrorResponseException $e) {
            $errors = (string) $e->getResponse()->getBody();
            // Log the error without breaking the page
            $this->getLogger()->error(sprintf('Bifrost error: %s', $errors), ['bifrost' => $e]);

            // Our request was not a success
            return Suggestions::create($e->getCode());
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->getLogger()->error(sprintf('Bifrost error: %s', $e->getMessage()), ['bifrost' => $e]);

            // Our request was not a success
            return Suggestions::create($e->getCode());
        }
    }

}
