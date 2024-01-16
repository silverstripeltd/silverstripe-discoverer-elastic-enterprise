<?php

namespace SilverStripe\SearchElastic\Query;

use Elastic\EnterpriseSearch\AppSearch\Schema\PaginationResponseObject;
use Elastic\EnterpriseSearch\AppSearch\Schema\SearchFields;
use Elastic\EnterpriseSearch\AppSearch\Schema\SearchRequestParams;
use Elastic\EnterpriseSearch\AppSearch\Schema\SimpleObject;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Search\Query\Query;
use stdClass;

class SearchQuery extends Query
{

    use Injectable;

    /**
     * Returns the string representation of the search params of this query, ready for sending to Elastic.
     */
    public function getSearchParams(): SearchRequestParams
    {
        $query = new SearchRequestParams($this->getQueryString());

        if (isset($this->rawFilters)) {
            $query->filters = $this->rawFilters;
        }

        if (isset($this->rawFacets)) {
            $query->facets = $this->rawFacets;
        }

        return $query;
    }

}
