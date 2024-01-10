<?php

namespace SilverStripe\ElasticAppSearch\Tests\Filter;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\Search\Filter\Criterion;
use SilverStripe\SearchElastic\Filter\CriterionAdaptor;
use SilverStripe\Search\Filter\CriterionAdaptor as CriterionAdaptorInterface;

class CriterionAdaptorTest extends SapphireTest
{

    /**
     * @dataProvider provideBasicComparisons
     */
    public function testStringComparison($comparison): void
    {
        $criterion = Criterion::create('fieldName', 'fieldValue', $comparison);
        // Not using injector, because I'm testing this specific class
        $adaptor = new CriterionAdaptor();
        $expected = [
            'fieldName' => 'fieldValue',
        ];

        $this->assertEquals($expected, $adaptor->prepareClause($criterion));
    }

    public function provideBasicComparisons(): array
    {
        return [
            [DataObject::CHANGE_VALUE],
        ];

        return [
            ['EQUAL'],
            ['NOT_EQUAL'],
            ['GREATER_EQUAL'],
            ['LESS_EQUAL'],
            ['IN'],
            ['NOT_IN'],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Injector::inst()->registerService(new CriterionAdaptor(), CriterionAdaptorInterface::class);
    }

}
