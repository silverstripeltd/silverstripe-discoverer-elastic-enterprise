<?php

namespace SilverStripe\SearchElastic\Service;

use SilverStripe\Search\Query\Result;
use SilverStripe\Search\Service\SearchService as SearchServiceInterface;
use SilverStripe\SearchElastic\Query\SearchQuery;

class SearchService implements SearchServiceInterface
{

    public function search(SearchQuery $query, string $indexName): Result
    {
        // TODO: Implement search() method.
    }

    public function multiSearch(): Result
    {
        // TODO: Implement multiSearch() method.
    }

}
