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
use SilverStripe\DiscovererElasticEnterprise\Service\Requests\ElasticsearchRequest;
use Throwable;

class SpellingSuggestionAdaptor extends BaseAdaptor implements SpellingSuggestionAdaptorInterface
{

    public function process(Suggestion $suggestion, string $indexName): Suggestions
    {
        // Instantiate our Suggestions class with empty data. This will still be returned if there is an Exception
        // during communication with Elastic (so that the page doesn't seriously break)
        $suggestions = Suggestions::create();

        try {
            $engine = $this->environmentizeIndex($indexName);
            $params = SpellingParamsProcessor::singleton()->getQueryParams($suggestion);
            $request = ElasticsearchRequest::create($engine, $params);

            $transportResponse = $this->getClient()->appSearch()->getTransport()->sendRequest($request->getRequest());
            $response = Injector::inst()->create(Response::class, $transportResponse);

            SpellingProcessor::singleton()->getProcessedSuggestions($suggestions, $response->asArray());
            // If we got this far, then the request was a success
            $suggestions->setSuccess(true);
        } catch (ClientErrorResponseException $e) {
            $errors = (string) $e->getResponse()->getBody();
            // Log the error without breaking the page
            $this->getLogger()->error(sprintf('Bifrost error: %s', $errors), ['bifrost' => $e]);
            // Our request was not a success
            $suggestions->setSuccess(false);
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->getLogger()->error(sprintf('Bifrost error: %s', $e->getMessage()), ['bifrost' => $e]);
            // Our request was not a success
            $suggestions->setSuccess(false);
        } finally {
            return $suggestions;
        }
    }

}
