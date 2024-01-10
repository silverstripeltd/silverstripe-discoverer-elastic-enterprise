<?php

namespace SilverStripe\ElasticAppSearch\Tests\Filter;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Search\Filter\Criterion;
use SilverStripe\SearchElastic\Filter\CriterionAdaptor;

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
            [Criterion::EQUAL],
        ];
    }

}
