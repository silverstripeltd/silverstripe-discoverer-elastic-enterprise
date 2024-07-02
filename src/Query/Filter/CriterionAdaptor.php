<?php

namespace SilverStripe\DiscovererElasticEnterprise\Query\Filter;

use Exception;
use SilverStripe\Discoverer\Query\Filter\Criterion;
use SilverStripe\Discoverer\Query\Filter\CriterionAdaptor as CriterionAdaptorInterface;

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
    public function prepareCriterion(Criterion $criterion): array
    {
        $comparison = $criterion->getComparison();

        if (in_array($comparison, self::UNSUPPORTED_COMPARISONS, true)) {
            throw new Exception(sprintf('Unsupported Elastic comparison "%s"', $comparison));
        }

        switch ($comparison) {
            case Criterion::RANGE:
                $value = $criterion->getValue();
                $from = $value['from'] ?? null;
                $to = $value['to'] ?? null;
                $range = [];

                if ($from) {
                    $range['from'] = $this->ensureDateFormat($from);
                }

                if ($to) {
                    $range['to'] = $this->ensureDateFormat($to);
                }

                if (!$range) {
                    throw new Exception('Range comparison $value must contain one (or both) of keys "from" and "to"');
                }

                return [
                    $criterion->getTarget() => $range,
                ];

            case Criterion::GREATER_EQUAL:
                return [
                    $criterion->getTarget() => [
                        'from' => $this->ensureDateFormat($criterion->getValue()),
                    ],
                ];

            case Criterion::LESS_EQUAL:
                return [
                    $criterion->getTarget() => [
                        'to' => $this->ensureDateFormat($criterion->getValue()),
                    ],
                ];

            default:
                return [
                    $criterion->getTarget() => $criterion->getValue(),
                ];
        }
    }

    private function ensureDateFormat(string $date): string
    {
        return date("Y-m-d\TH:i:sP", strtotime($date));
    }

}
