<?php

namespace SilverStripe\DiscovererElasticEnterprise\Processors;

use Elastic\EnterpriseSearch\AppSearch\Schema\QuerySuggestionRequest;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Discoverer\Query\Suggestion;
use stdClass;

class SuggestionParamsProcessor
{

    use Injectable;

    public function getQueryParams(Suggestion $suggestion): QuerySuggestionRequest
    {
        $querySuggestionParams = new QuerySuggestionRequest();
        $querySuggestionParams->query = $suggestion->getQueryString();

        $limit = $suggestion->getLimit();
        $fields = $suggestion->getFields();

        if ($limit) {
            $querySuggestionParams->size = $limit;
        }

        if ($fields) {
            $types = new stdClass();
            $types->documents = new stdClass();
            $types->documents->fields = $fields;

            $querySuggestionParams->types = $types;
        }

        return $querySuggestionParams;
    }

}
