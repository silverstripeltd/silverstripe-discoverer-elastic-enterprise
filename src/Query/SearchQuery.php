<?php

namespace SilverStripe\SearchElastic\Query;

use Elastic\EnterpriseSearch\AppSearch\Schema\PaginationResponseObject;
use Elastic\EnterpriseSearch\AppSearch\Schema\SearchFields;
use Elastic\EnterpriseSearch\AppSearch\Schema\SearchRequestParams;
use Elastic\EnterpriseSearch\AppSearch\Schema\SimpleObject;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Search\Query\Facet;
use SilverStripe\Search\Query\Filter;
use SilverStripe\Search\Query\Query;
use stdClass;

class SearchQuery extends Query
{
    use Configurable;
    use Injectable;

    private ?SimpleObject $rawFilters = null;

    private ?SimpleObject $rawFacets = null;

    /**
     * Sets the raw 'facets' attribute for returning metadata related to the search query. See the docs for help:
     * https://swiftype.com/documentation/app-search/api/search/facets.
     */
    public function addRawFacets(SimpleObject $facets): self
    {
        $this->rawFacets = $facets;

        return $this;
    }

    /**
     * Returns the string representation of the search params of this query, ready for sending to Elastic.
     */
    public function getSearchParams(): SearchRequestParams
    {
        $query = new SearchRequestParams($this->getQueryString());

        if (isset($this->rawFilters)) {
            $query->filters = $this->rawFilters;
        }

        if (isset($this->rawFacets)) {
            $query->facets = $this->rawFacets;
        }

        if (isset($this->sort)) {
            $query->sort = $this->getSortForRequest();
        }

        if (isset($this->resultFields)) {
            $query->result_fields = $this->getResultFieldsForRequest();
        }

        if (isset($this->searchFields)) {
            $query->search_fields = $this->getSearchFieldsForRequest();
        }

        if (isset($this->pageNum, $this->pageSize)) {
            $query->page = $this->getPaginationForRequest();
        }

        return $query;
    }

    private function getSortForRequest(): ?array
    {
        if (!isset($this->sort)) {
            return null;
        }

        // finally sort by score as a fallback
        if (empty(array_column($this->sort, '_score'))) {
            $this->sort[] = ['_score' => 'desc'];
        }

        return $this->sort;
    }

    private function getPaginationForRequest(): ?PaginationResponseObject
    {
        $page = null;

        if (isset($this->pageNum, $this->pageSize)) {
            $page = new PaginationResponseObject();
            $page->size = $this->pageSize;
            $page->current = $this->pageNum;
        }

        return $page;
    }

    private function getResultFieldsForRequest(): ?SimpleObject
    {
        $resultFields = null;

        if (isset($this->resultFields)) {
            $resultFields = new SimpleObject();

            // Ensure we include the default fields so we can map these documents back to Silverstripe DataObjects
            $resultFields->record_base_class = new stdClass();
            $resultFields->record_base_class->raw = new stdClass();
            $resultFields->record_id = $resultFields->record_base_class;

            foreach ($this->resultFields as $field => $options) {
                $type = $options['type'];
                $size = $options['size'];

                $resultFields->{$field} = new stdClass();
                $resultFields->{$field}->{$type} = new stdClass();

                if ($size) {
                    $resultFields->{$field}->{$type}->size = $size;
                }
            }
        }

        return $resultFields;
    }

    private function getSearchFieldsForRequest(): ?SearchFields
    {
        $searchFields = null;

        if (isset($this->searchFields)) {
            $searchFields = new SearchFields();

            foreach ($this->searchFields as $field => $weight) {
                $searchFields->{$field} = new stdClass();

                // Add optional weight but only if it's specified and valid
                if (is_numeric($weight) && $weight > 0) {
                    $searchFields->{$field}->weight = $weight;
                }
            }
        }

        return $searchFields;
    }
}
