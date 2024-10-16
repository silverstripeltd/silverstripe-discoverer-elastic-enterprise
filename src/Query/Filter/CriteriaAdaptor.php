<?php

namespace SilverStripe\DiscovererElasticEnterprise\Query\Filter;

use Exception;
use SilverStripe\Discoverer\Query\Filter\Criteria;
use SilverStripe\Discoverer\Query\Filter\CriteriaAdaptor as CriteriaAdaptorInterface;
use SilverStripe\Discoverer\Query\Filter\Criterion;

/**
 * Docs describing what filtering is possible:
 * https://www.elastic.co/guide/en/app-search/master/filters.html
 */
class CriteriaAdaptor implements CriteriaAdaptorInterface
{

    private const NONE_COMPARISONS = [
        Criterion::NOT_IN,
        Criterion::NOT_EQUAL,
    ];

    public function prepareCriteria(Criteria $criteria): array
    {
        $all = [];
        $any = [];
        $none = [];

        // You can't have a mixture of clauses for Elastic. You either have all nested Criteria, or all Criterion
        $clauseType = null;

        foreach ($criteria->getClauses() as $clause) {
            if (!$clauseType) {
                $clauseType = $clause::class;
            }

            if (!$clause instanceof $clauseType) {
                throw new Exception(
                    'Elastic does not support a mixture of nested and un-nested clauses. IE: a Criteria can'
                    . ' only contain other Criteria or Criterion, not a mixture of both'
                );
            }

            if ($clause instanceof Criterion) {
                // NONE comparisons have to go into a separate array called "none"
                if (in_array($clause->getComparison(), self::NONE_COMPARISONS, true)) {
                    $none[] = $clause->getPreparedClause();

                    continue;
                }

                switch ($criteria->getConjunction()) {
                    case Criteria::CONJUNCTION_OR:
                        $any[] = $clause->getPreparedClause();

                        break;

                    default:
                        $all[] = $clause->getPreparedClause();
                }

                continue;
            }

            switch ($criteria->getConjunction()) {
                case Criteria::CONJUNCTION_OR:
                    $any[] = $clause->getPreparedClause();

                    break;

                default:
                    $all[] = $clause->getPreparedClause();
            }
        }

        return [
            'all' => $all,
            'any' => $any,
            'none' => $none,
        ];
    }

}
