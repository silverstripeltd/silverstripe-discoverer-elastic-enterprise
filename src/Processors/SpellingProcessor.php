<?php

namespace SilverStripe\DiscovererElasticEnterprise\Processors;

use Exception;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Discoverer\Service\Results\Field;
use SilverStripe\Discoverer\Service\Results\Suggestions;

class SpellingProcessor
{

    use Injectable;

    public function getProcessedSuggestions(Suggestions $suggestions, array $response): void
    {
        // Check that we have all critical fields in our Elastic response
        $this->validateResponse($response);

        $documentSuggestions = $response['suggest'] ?? [];
        $deduplicatedSuggestions = [];

        foreach ($documentSuggestions as $fieldName => $documentSuggestion) {
            $suggestion = array_pop($documentSuggestion);
            $options = $suggestion['options'] ?? null;

            if (!$suggestion || !$options) {
                continue;
            }

            foreach ($options as $option) {
                $deduplicatedSuggestions[]= $option['text'];
            }
        }

        $deduplicatedSuggestions = array_unique($deduplicatedSuggestions);

        foreach ($deduplicatedSuggestions as $suggestion) {
            $suggestions->addSuggestion(Field::create($suggestion));
        }
    }

    private function validateResponse(array $response): void
    {
        // If any errors are present, then let's throw and track what they were
        if (array_key_exists('errors', $response)) {
            throw new Exception(sprintf('Elastic response contained errors: %s', json_encode($response['errors'])));
        }

        // The top level fields that we expect to receive from Elastic for each search
        $suggest = $response['suggest'] ?? null;

        // Check if any required fields are missing
        $missingTopLevelFields = [];

        if (!$suggest) {
            $missingTopLevelFields[] = 'suggest';
        }

        // We were missing one or more required top level fields
        if ($missingTopLevelFields) {
            throw new Exception(sprintf(
                'Missing required top level fields for query suggestions: %s',
                implode(', ', $missingTopLevelFields)
            ));
        }
    }

}
