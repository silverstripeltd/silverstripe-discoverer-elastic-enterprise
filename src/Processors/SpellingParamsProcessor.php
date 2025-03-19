<?php

namespace SilverStripe\DiscovererElasticEnterprise\Processors;

use Elastic\EnterpriseSearch\AppSearch\Schema\EsSearchParams;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Discoverer\Query\Suggestion;
use stdClass;

class SpellingParamsProcessor
{

    use Injectable;

    public function getQueryParams(Suggestion $suggestion): EsSearchParams
    {
        $spellingSuggestionParams = Injector::inst()->create(EsSearchParams::class);
        $suggest = new stdClass();
        // global suggest text
        $suggest->text = $suggestion->getQueryString();
        $spellingSuggestionParams->suggest = $suggest;

        $limit = $suggestion->getLimit();
        $fields = $suggestion->getFields();

        if ($fields) {
            // add term per field
            foreach ($fields as $fieldName) {
                $term = new stdClass();
                $term->field = $fieldName;

                if ($limit) {
                    $term->size = $limit;
                }

                $spellingSuggestionParams->suggest->{$fieldName} = new stdClass();
                $spellingSuggestionParams->suggest->{$fieldName}->term = $term;
            }
        }

        return $spellingSuggestionParams;
    }

}
