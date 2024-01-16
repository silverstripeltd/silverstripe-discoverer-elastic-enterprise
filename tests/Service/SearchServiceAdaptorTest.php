<?php

namespace SilverStripe\SearchElastic\Tests\Service;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Search\Query\Query;
use SilverStripe\Search\Service\SearchService;

class SearchServiceAdaptorTest extends SapphireTest
{

    public function testSearch(): void
    {
        $query = Query::create('product');
        $service = SearchService::create($query);
        $service->search($query);
    }

}
