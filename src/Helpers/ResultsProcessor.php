<?php

namespace SilverStripe\SearchElastic\Helpers;

use Elastic\EnterpriseSearch\Response\Response;
use Exception;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Search\Service\Result\Facet;
use SilverStripe\Search\Service\Result\FacetData;
use SilverStripe\Search\Service\Result\Field;
use SilverStripe\Search\Service\Result\Record;
use SilverStripe\Search\Service\Result\Results;

class ResultsProcessor
{

    use Configurable;

    /**
     * Elastic has a default limit of handling 100 pages. If you request a page beyond this limit
     * then an error occurs. We use this to limit the number of pages that are returned.
     */
    private static int $elastic_page_limit = 100;

    /**
     * Elastic has a default limit of 10000 results returned in a single query
     */
    private static int $elastic_results_limit = 10000;

    /**
     * @throws Exception
     */
    public static function processResponse(Results $results, Response $response): void
    {
        self::processMetaData($results, $response);
        self::processRecords($results, $response);
        self::processFacets($results, $response);
    }

    private static function processMetaData(Results $results, Response $response): void
    {
        $responseArray = $response->asArray();

        $pageSize = $responseArray['meta']['page']['size'] ?? 0;
        $pageLimit = self::config()->get('elastic_page_limit') ?? 0;
        $resultsLimit = self::config()->get('elastic_results_limit') ?? 0;
        $currentPage = $responseArray['meta']['page']['current'] ?? 1;

        // Calculate the total paginated results that can be handled, taking into account the default elastic limits.
        // The page size also needs to be considered here so that we only handle the number of results rendered
        // on the first 100 pages (elastic_page_limit * $pageSize).
        $totalResults = min([
            $responseArray['meta']['page']['total_results'] ?? 0,
            $pageLimit * $pageSize,
            $resultsLimit
        ]);

        $records = $results->getRecords();
        $records->setLimitItems(false);
        $records->setPageLength($pageSize);
        $records->setTotalItems($totalResults);
        $records->setCurrentPage($currentPage);
    }

    /**
     * @throws Exception
     */
    private static function processRecords(Results $results, Response $response): void
    {
        $responseArray = $response->asArray();

        if (!array_key_exists('results', $responseArray)) {
            throw new Exception('Elastic Response contained to results array');
        }

        foreach ($responseArray['results'] as $result) {
            $record = Record::create();

            foreach ($result as $fieldName => $valueFields) {
                // Convert snake_case (Elastic's field name format) to PascalCase (Silverstripe's field name format)
                $formattedFieldName = str_replace('_', '', ucwords($fieldName, '_'));

                $field = Field::create();
                $field->setRaw($valueFields['raw'] ?? null);
                $field->setSnippet($valueFields['snippet'] ?? null);

                /** @see Record::__set() */
                $result->{$formattedFieldName} = $field;
            }

            $results->addRecord($record);
        }
    }

    private static function processFacets(Results $results, Response $response): void
    {
        $responseArray = $response->asArray();
        $facets = $responseArray['facets'] ?? null;

        if (!is_array($facets)) {
            return;
        }

        foreach ($facets as $property => $facetResults) {
            foreach ($facetResults as $index => $facetResult) {
                $facet = Facet::create();
                $facet->setProperty($property);
                $facet->setName($facetResult['name'] ?? $index);

                foreach ($facetResult['data'] as $resultData) {
                    $facetData = FacetData::create();
                    $facetData->setValue($resultData['value'] ?? '');
                    $facetData->setFrom($resultData['from'] ?? '');
                    $facetData->setTo($resultData['to'] ?? '');
                    $facetData->setCount($resultData['count'] ?? '');

                    $facet->addData($facetData);
                }

                $results->addFacet($facet);
            }
        }
    }

}
