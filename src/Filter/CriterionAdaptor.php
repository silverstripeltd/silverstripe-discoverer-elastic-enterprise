<?php

namespace SilverStripe\SearchElastic\Filter;

use Exception;
use SilverStripe\Search\Filter\Criterion;
use SilverStripe\Search\Filter\CriterionAdaptor as CriterionAdaptorInterface;

class CriterionAdaptor implements CriterionAdaptorInterface
{

    private const UNSUPPORTED_COMPARISONS = [
        Criterion::LESS_THAN,
        Criterion::GREATER_THAN,
        Criterion::IS_NULL,
        Criterion::IS_NOT_NULL,
    ];

    /**
     * @throws Exception
     */
    public function prepareClause(Criterion $criterion): array
    {
        $comparison = $criterion->getComparison();

        if (in_array($comparison, self::UNSUPPORTED_COMPARISONS)) {
            throw new Exception(sprintf('Unsupported Elastic comparison "%s"', $comparison));
        }

        switch ($comparison) {
            case Criterion::RANGE:
                $value = $criterion->getValue();
                $from = $value['from'] ?? '';
                $to = $value['to'] ?? '';

                if (!$from || !$to) {
                    throw new Exception('Range comparison value must contain array keys "from" and "to"');
                }

                return [
                    $criterion->getTarget() => [
                        'from' => $from,
                        'to' => $to,
                    ]
                ];
            default:
                return [
                    $criterion->getTarget() => $criterion->getValue(),
                ];
        }
    }

}
