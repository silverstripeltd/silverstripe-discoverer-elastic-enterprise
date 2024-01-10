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

    private string $query = '';

    private ?SimpleObject $rawFilters = null;

    private ?SimpleObject $rawFacets = null;

    private ?array $resultFields = null;

    private ?array $sort = null;

    private ?array $searchFields = null;

    private ?int $pageSize = null;

    private ?int $pageNum = null;

    /**
     * Set the query string that all documents must match in order to be returned. This can be set to an empty string to
     * return all documents
     */
    public function setQueryString(string $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * The query string set on this query
     */
    public function getQueryString(): ?string
    {
        return $this->query;
    }

    /**
     * Add a sort method to the list, see:
     * https://www.elastic.co/guide/en/app-search/current/sort.html.
     *
     * @param string $direction valid values are asc/desc
     */
    public function addSort(string $fieldName, string $direction = 'asc'): self
    {
        if (!isset($this->sort)) {
            $this->sort = [];
        }

        $this->sort[] = [$fieldName => mb_strtolower($direction)];

        return $this;
    }

    /**
     * Adds multiple sort methods at once.
     *
     * @param array $sortMethods [$fieldname => $direction]
     */
    public function addSorts(array $sortMethods): self
    {
        foreach ($sortMethods as $fieldName => $direction) {
            $this->addSort($fieldName, $direction);
        }

        return $this;
    }

    /**
     * Sets the raw 'filters' attribute for filtering results. For more information on how to create filters, consult
     * the Elastic App Search documentation: https://www.elastic.co/guide/en/app-search/current/filters.html.
     *
     * @todo It would be nice to allow for PHP-built filters (e.g. built from objects rather than needing the developer
     * to figure out how Elastic's 'filters' key works) but that's a feature for a later date.
     */
    public function addRawFilters(SimpleObject $filters): self
    {
        $this->rawFilters = $filters;

        return $this;
    }

    /**
     * Sets the raw 'facets' attribute for returning metadata related to the search query. See the docs for help:
     * https://swiftype.com/documentation/app-search/api/search/facets.
     */
    public function addRawFacets(SimpleObject $facets): self
    {
        $this->rawFacets = $facets;

        return $this;
    }

    public function setFacets(array $facet): self
    {
        // TODO: Implement addFacet() method.
        return $this;
    }

    public function addResultField(string $field, string $type = 'raw', int $size = 0): self
    {
        if (!isset($this->resultFields)) {
            $this->resultFields = [];
        }

        $this->resultFields[$field] = [
            'type' => $type,
            'size' => $size,
        ];

        return $this;
    }

    public function addSearchField(string $field, int $weight = 0): self
    {
        if (!isset($this->searchFields)) {
            $this->searchFields = [];
        }

        $this->searchFields[$field] = $weight;

        return $this;
    }

    public function setPageSize(int $pageSize): self
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    public function setPageNum(int $pageNum): self
    {
        $this->pageNum = $pageNum;

        return $this;
    }

    public function setPagination(int $pageSize, int $pageNum): self
    {
        $this->pageSize = $pageSize;
        $this->pageNum = $pageNum;

        return $this;
    }

    public function hasPagination(): bool
    {
        return isset($this->pageNum) || isset($this->pageSize);
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
