<?php

namespace SilverStripe\SearchElastic\Tests\Query;

use Elastic\OpenApi\Codegen\Serializer\SmartSerializer;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\SearchElastic\Query\MultiSearchQuery;
use SilverStripe\SearchElastic\Query\SearchQuery;

class MultiSearchQueryTest extends SapphireTest
{
    public function testAddQuery(): void
    {
        /** @var MultiSearchQuery $multisearchQuery */
        $multisearchQuery = Injector::inst()->create(MultiSearchQuery::class);

        /** @var SearchQuery $fooQuery */
        $fooQuery = Injector::inst()->create(SearchQuery::class);
        $fooQuery->setQueryString('foo');

        $multisearchQuery->addQuery($fooQuery);

        $this->assertEquals($fooQuery, $multisearchQuery->getQueries()[0]);

        /** @var SearchQuery $barQuery */
        $barQuery = Injector::inst()->create(SearchQuery::class);
        $barQuery->setQueryString('bar');

        $multisearchQuery->addQuery($barQuery);

        $this->assertEquals($fooQuery, $multisearchQuery->getQueries()[0]);
        $this->assertEquals($barQuery, $multisearchQuery->getQueries()[1]);
    }

    public function testSetQueries(): void
    {
        /** @var MultiSearchQuery $multisearchQuery */
        $multisearchQuery = Injector::inst()->create(MultiSearchQuery::class);

        $fooQuery = Injector::inst()->create(SearchQuery::class);
        $fooQuery->setQueryString('foo');

        /** @var SearchQuery $barQuery */
        $barQuery = Injector::inst()->create(SearchQuery::class);
        $barQuery->setQueryString('bar');

        $multisearchQuery->setQueries([$fooQuery, $barQuery]);

        $this->assertCount(2, $multisearchQuery->getQueries());
        $this->assertEquals($fooQuery, $multisearchQuery->getQueries()[0]);
        $this->assertEquals($barQuery, $multisearchQuery->getQueries()[1]);

        // Assert that a too-big array is reduced to 10
        $tooManyQueries = array_pad([], 11, $fooQuery);

        $multisearchQuery->setQueries($tooManyQueries);
        $this->assertCount(10, $multisearchQuery->getQueries());
    }

    public function testRenderQueries(): void
    {
        /** @var MultiSearchQuery $multisearchQuery */
        $multisearchQuery = Injector::inst()->create(MultiSearchQuery::class);

        /** @var SearchQuery $fooQuery */
        $fooQuery = Injector::inst()->create(SearchQuery::class);
        $fooQuery->setQueryString('foo');

        /** @var SearchQuery $barQuery */
        $barQuery = Injector::inst()->create(SearchQuery::class);
        $barQuery->setQueryString('bar');
        $barQuery->addSorts(
            [
                'baz' => 'desc',
                'qux' => 'asc',
            ]
        );

        $multisearchQuery->setQueries([$fooQuery, $barQuery]);
        $sortedQueries = $multisearchQuery->getSearchParams();

        $this->assertSame($fooQuery->getSearchParams()->query, 'foo');
        $this->assertSame($barQuery->getSearchParams()->query, 'bar');
        $this->assertSame($barQuery->getSearchParams()->sort, [
            ['baz' => "desc"],
            ['qux' => "asc"],
            ['_score' => "desc"],
        ]);
    }
}
