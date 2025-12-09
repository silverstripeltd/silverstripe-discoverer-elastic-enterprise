<?php

namespace SilverStripe\DiscovererElasticEnterprise\Tests\Query\Filter;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Discoverer\Query\Filter\Criterion;
use SilverStripe\Discoverer\Query\Filter\CriterionAdaptor as CriterionAdaptorInterface;
use SilverStripe\DiscovererElasticEnterprise\Query\Filter\CriterionAdaptor;

class CriterionAdaptorTest extends SapphireTest
{

    #[DataProvider('provideBasicComparisons')]
    public function testBasicComparison(string $comparison): void
    {
        $criterion = Criterion::create('fieldName', 'fieldValue', $comparison);
        // Not using injector, because I'm testing this specific class
        $adaptor = new CriterionAdaptor();
        $expected = [
            'fieldName' => 'fieldValue',
        ];

        $this->assertEquals($expected, $adaptor->prepareCriterion($criterion));
    }

    public static function provideBasicComparisons(): array
    {
        return [
            'equal' => [Criterion::EQUAL],
            'not_equal' => [Criterion::NOT_EQUAL],
        ];
    }

    #[DataProvider('provideGreaterLessComparisons')]
    public function testGreaterLessComparison(string $comparison, string $key): void
    {
        $date = '2024-01-01 01:02:03';
        $criterion = Criterion::create('fieldName', $date, $comparison);
        // Not using injector, because I'm testing this specific class
        $adaptor = new CriterionAdaptor();

        $expected = [
            'fieldName' => [
                $key => $date,
            ],
        ];

        $this->assertEquals($expected, $adaptor->prepareCriterion($criterion));
    }

    public static function provideGreaterLessComparisons(): array
    {
        return [
            'greater_equal' => [Criterion::GREATER_EQUAL, 'from'],
            'less_equal' => [Criterion::LESS_EQUAL, 'to'],
        ];
    }

    #[DataProvider('provideUnsupportedComparisons')]
    public function testUnsupportedComparison(string $comparison): void
    {
        $this->expectExceptionMessage(sprintf('Unsupported Elastic comparison "%s"', $comparison));

        $criterion = Criterion::create('fieldName', 'fieldValue', $comparison);
        // Not using injector, because I'm testing this specific class
        $adaptor = new CriterionAdaptor();
        // Should throw our Exception
        $adaptor->prepareCriterion($criterion);
    }

    public static function provideUnsupportedComparisons(): array
    {
        return [
            'less_than' => [Criterion::LESS_THAN],
            'greater_than' => [Criterion::GREATER_THAN],
            'is_null' => [Criterion::IS_NULL],
            'is_not_null' => [Criterion::IS_NOT_NULL],
        ];
    }

    public function testRangeToFromComparison(): void
    {
        $range = [
            'from' => 1,
            'to' => 2,
        ];
        $criterion = Criterion::create('fieldName', $range, Criterion::RANGE);
        // Not using injector, because I'm testing this specific class
        $adaptor = new CriterionAdaptor();
        $expected = [
            'fieldName' => $range,
        ];

        $this->assertEquals($expected, $adaptor->prepareCriterion($criterion));
    }

    public function testRangeToComparison(): void
    {
        $range = [
            'to' => 2,
        ];
        $criterion = Criterion::create('fieldName', $range, Criterion::RANGE);
        // Not using injector, because I'm testing this specific class
        $adaptor = new CriterionAdaptor();
        $expected = [
            'fieldName' => $range,
        ];

        $this->assertEquals($expected, $adaptor->prepareCriterion($criterion));
    }

    public function testRangeFromComparison(): void
    {
        $range = [
            'from' => 1,
        ];
        $criterion = Criterion::create('fieldName', $range, Criterion::RANGE);
        // Not using injector, because I'm testing this specific class
        $adaptor = new CriterionAdaptor();
        $expected = [
            'fieldName' => $range,
        ];

        $this->assertEquals($expected, $adaptor->prepareCriterion($criterion));
    }

    #[DataProvider('provideInComparisons')]
    public function testValidInComparison(string $comparison): void
    {
        $range = [
            1,
            2,
        ];
        $criterion = Criterion::create('fieldName', $range, $comparison);
        // Not using injector, because I'm testing this specific class
        $adaptor = new CriterionAdaptor();
        $expected = [
            'fieldName' => $range,
        ];

        $this->assertEquals($expected, $adaptor->prepareCriterion($criterion));
    }

    public static function provideInComparisons(): array
    {
        return [
            'in' => [Criterion::IN],
            'not_in' => [Criterion::NOT_IN],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Injector::inst()->registerService(new CriterionAdaptor(), CriterionAdaptorInterface::class);
    }

}
