<?php

namespace SilverStripe\SearchElastic\Service;

use Elastic\EnterpriseSearch\AppSearch\Request\Search;
use Elastic\EnterpriseSearch\Client;
use Elastic\EnterpriseSearch\Response\Response;
use Exception;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\Search\Query\Query;
use SilverStripe\Search\Service\Result\Field;
use SilverStripe\Search\Service\Result\Record;
use SilverStripe\Search\Service\Result\Results;
use SilverStripe\Search\Service\SearchServiceAdaptor as SearchServiceAdaptorInterface;
use SilverStripe\SearchElastic\Helpers\QueryParamsProcessor;
use SilverStripe\SearchElastic\Helpers\ResultsProcessor;

class SearchServiceAdaptor implements SearchServiceAdaptorInterface
{

    private ?Client $client = null;

    private static array $dependencies = [
        'client' => '%$' . Client::class,
    ];

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): void
    {
        $this->client = $client;
    }

    public function search(Query $query, ?string $indexName = null): Results
    {
        $params = QueryParamsProcessor::getQueryParams($query);
        $engine = $this->environmentizeIndex($indexName);
        $request = new Search($engine, $params);
        $response = $this->client->appSearch()->search($request);

        Debug::dump($response->asArray());
        $results = Results::create($query);

        ResultsProcessor::processResponse($results, $response);

        return $results;
    }

    public function logClickThrough(): void
    {
        // TODO: Implement logClickThrough() method.
    }

    private function environmentizeIndex(string $indexName): string
    {
        $variant = Environment::getEnv('ENTERPRISE_SEARCH_ENGINE_PREFIX');

        if ($variant) {
            return sprintf('%s-%s', $variant, $indexName);
        }

        return $indexName;
    }

    private function validateResponse(Response $response): void
    {
        $responseArray = $response->asArray();

        // Ensure we don't have errors in our response
        if (array_key_exists('errors', $responseArray)) {
            throw new LogicException('Response appears to be from Elastic but is an error, not a valid search result');
        }

        // Ensure we have both required top-level array keys (`meta` and `results`)
        if (!array_key_exists('meta', $response) || !array_key_exists('results', $response)) {
            throw new InvalidArgumentException('Response decoded as JSON but is not an Elastic search response');
        }
    }

}
