<?php

namespace SilverStripe\ElasticAppSearch\Tests\Service;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\SearchElastic\Query\MultiSearchQuery;
use SilverStripe\SearchElastic\Query\SearchQuery;
use SilverStripe\SearchElastic\Service\MultiSearchResult;
use SilverStripe\SearchElastic\Service\SearchResult;

class MultiSearchResultTest extends SapphireTest
{
    public function testResponseWithoutRequestIDAllowed(): void
    {
        /** @var MultiSearchQuery $multisearchQuery */
        $multisearchQuery = Injector::inst()->create(MultiSearchQuery::class);

        /** @var SearchQuery $fooQuery */
        $fooQuery = Injector::inst()->create(SearchQuery::class);
        $fooQuery->setQuery('foo');
        $multisearchQuery->addQuery($fooQuery);

        $result = new MultiSearchResult($multisearchQuery, [
            [
                'query' => 'foo',
                'meta' => [
                    'page' => [
                        'current' => 1,
                        'size' => 10,
                        'total_pages' => 1,
                        'total_results' => 10,
                    ],
                ],
                'results' => []
            ]
        ]);

        $this->assertCount(1, $result->getResults());
        $singleResult = $result->getResults()[0];
        $this->assertInstanceOf(SearchResult::class, $singleResult);
        $blankList = PaginatedList::create(ArrayList::create())
            ->setLimitItems(false)
            ->setPageLength(10)
            ->setTotalItems(10)
            ->setCurrentPage(1);
        $this->assertEquals($blankList, $singleResult->getResults());
    }
}
