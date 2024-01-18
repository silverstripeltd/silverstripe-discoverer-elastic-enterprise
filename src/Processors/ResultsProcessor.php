<?php

namespace SilverStripe\SearchElastic\Processors;

use Elastic\EnterpriseSearch\Response\Response;
use Exception;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Search\Analytics\AnalyticsData;
use SilverStripe\Search\Analytics\AnalyticsMiddleware;
use SilverStripe\Search\Query\Query;
use SilverStripe\Search\Service\Results\Facet;
use SilverStripe\Search\Service\Results\FacetData;
use SilverStripe\Search\Service\Results\Field;
use SilverStripe\Search\Service\Results\Record;
use SilverStripe\Search\Service\Results\Results;

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
    public static function getProcessedResults(Query $query, Response $response): Results
    {
        self::validateResponse($response);

        $results = Results::create($query);

        self::processMetaData($results, $response);
        self::processRecords($results, $response);
        self::processFacets($results, $response);

        return $results;
    }

    private static function validateResponse(Response $response): void
    {
        $responseArray = $response->asArray();

        // If any errors are present, then let's throw and track what they were
        if (array_key_exists('errors', $responseArray)) {
            throw new Exception(sprintf('Elastic response contained errors: %s', json_encode($responseArray['errors'])));
        }

        // The top level fields that we expect to receive from Elastic for each search
        $meta = $responseArray['meta'] ?? null;
        $results = $responseArray['results'] ?? null;

        // Check if any required fields are missing
        $missingTopLevelFields = [];

        // Basic falsy check is fine here. An empty `meta` would still be an error
        if (!$meta) {
            $missingTopLevelFields[] = 'meta';
        }

        // Specifically checking for null, because an empty results array is a valid response
        if ($results === null) {
            $missingTopLevelFields[] = 'results';
        }

        // We were missing one or more required top level fields
        if ($missingTopLevelFields) {
            throw new Exception('Missing required top level fields: %s', implode(', ', $missingTopLevelFields));
        }

        // We expect every search to contain a value for `request_id`
        $requestId = $meta['request_id'] ?? null;

        if (!$requestId) {
            throw new Exception('Expected value for meta.request_id');
        }

        $engineName = $meta['engine']['name'] ?? null;

        if (!$engineName) {
            throw new Exception('Expected value for meta.engine.name');
        }

        // We expect every search to contain pagination results, even if there is only 1 page of 0 results
        $pagination = $meta['page'] ?? null;

        // Ensure we have pagination results
        if (!$pagination) {
            throw new Exception('Missing array structure for meta.page in Elastic search response');
        }

        $missingPaginationFields = [];
        $expectedPagination = [
            'current',
            'size',
            'total_pages',
            'total_results'
        ];

        foreach ($expectedPagination as $expectedKey) {
            if (!array_key_exists($expectedKey, $pagination)) {
                $missingPaginationFields[] = $expectedKey;
            }
        }

        if ($missingPaginationFields) {
            throw new Exception('Missing required pagination fields: %s', implode(', ', $missingTopLevelFields));
        }
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

        // Only used if analytics are enabled
        $requestId = $responseArray['meta']['request_id'] ?? null;
        $engineName = $responseArray['meta']['engine']['name'] ?? null;
        $queryString = $results->getQuery()->getQueryString();

        // Shouldn't ever be null, since we passed the validation step which requires these fields
        if (!$requestId || !$engineName) {
            throw new Exception('Expected value for meta.request_id and meta.engine.name');
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
                $record->{$formattedFieldName} = $field;
            }

            if (Environment::getEnv(AnalyticsMiddleware::ENV_ANALYTICS_ENABLED)) {
                // This field should always be there, as it's the default ID field in Elastic. We won't break stuff
                // if it isn't there though - better that search works without analytics
                $documentId = $result['id']['raw'] ?? null;

                $analyticsData = AnalyticsData::create();
                $analyticsData->setQueryString($queryString);
                $analyticsData->setEngineName($engineName);
                $analyticsData->setDocumentId($documentId);
                $analyticsData->setRequestId($requestId);

                $record->setAnalyticsData($analyticsData);
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
