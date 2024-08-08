<?php

namespace SilverStripe\DiscovererElasticEnterprise\Processors;

use Exception;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Discoverer\Service\Results\Suggestions;

class SuggestionsProcessor
{

    use Injectable;

    public function getProcessedSuggestions(Suggestions $suggestions, array $response): void
    {
        // Check that we have all critical fields in our Elastic response
        $this->validateResponse($response);

        $documentSuggestions = $response['results']['documents'] ?? [];

        foreach ($documentSuggestions as $documentSuggestion) {
            $suggestion = $documentSuggestion['suggestion'] ?? null;

            if (!$suggestion) {
                continue;
            }

            $suggestions->addSuggestion($suggestion);
        }
    }

    private function validateResponse(array $response): void
    {
        // If any errors are present, then let's throw and track what they were
        if (array_key_exists('errors', $response)) {
            throw new Exception(sprintf('Elastic response contained errors: %s', json_encode($response['errors'])));
        }

        // The top level fields that we expect to receive from Elastic for each search
        $meta = $response['meta'] ?? null;
        $results = $response['results'] ?? null;
        // Check if any required fields are missing
        $missingTopLevelFields = [];

        // Basic falsy check is fine here. An empty `meta` would still be an error
        if (!$meta) {
            $missingTopLevelFields[] = 'meta';
        }

        // Specifically checking for null, because an empty results array is a valid response
        if ($results === null) {
            $missingTopLevelFields[] = 'results';
        }

        // We were missing one or more required top level fields
        if ($missingTopLevelFields) {
            throw new Exception(sprintf(
                'Missing required top level fields: %s',
                implode(', ', $missingTopLevelFields)
            ));
        }
    }

}
