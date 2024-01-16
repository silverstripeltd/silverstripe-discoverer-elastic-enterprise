<?php

namespace SilverStripe\SearchElastic\Query\Facet;

use Elastic\EnterpriseSearch\AppSearch\Schema\SimpleObject;
use SilverStripe\Search\Query\Facet\Facet;
use SilverStripe\Search\Query\Facet\FacetAdaptor as FacetAdaptorInterface;
use SilverStripe\Search\Query\Facet\FacetCollection;

class FacetAdaptor implements FacetAdaptorInterface
{

    public function prepareFacets(FacetCollection $facetCollection): mixed
    {
        $facets = new SimpleObject();

        foreach ($facetCollection->getFacets() as $facet) {
            $property = $facet->getProperty();

            if (!property_exists($facets, $property)) {
                $facets->{$property} = [$property];
            }

            $facets->{$property}[] = $this->prepareFacet($facet);
        }

        return $facets;
    }

    private function prepareFacet(Facet $facet): array
    {
        $preparedFacet = [];
        $preparedFacet['type'] = $facet->getType();
        $preparedFacet['name'] = $facet->getName();

        if ($facet->getType() === Facet::TYPE_VALUE) {
            if ($facet->getLimit()) {
                $preparedFacet['size'] = $facet->getLimit();
            }

            return $preparedFacet;
        }

        $ranges = $this->prepareRanges($facet);

        if ($ranges) {
            $preparedFacet['ranges'] = $ranges;
        }

        return $preparedFacet;
    }

    private function prepareRanges(Facet $facet): ?array
    {
        if (!$facet->getRanges()) {
            return null;
        }

        $ranges = [];

        foreach ($facet->getRanges() as $range) {
            $preparedRange = [];
            $from = $range->getFrom();
            $to = $range->getTo();
            $name = $range->getName();

            if ($from) {
                $preparedRange['from'] = $from;
            }

            if ($to) {
                $preparedRange['to'] = $to;
            }

            if ($name) {
                $preparedRange['name'] = $name;
            }

            $ranges[] = $preparedRange;
        }

        return $ranges;
    }

}
