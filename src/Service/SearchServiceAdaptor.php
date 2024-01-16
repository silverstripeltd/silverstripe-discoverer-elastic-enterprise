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
use SilverStripe\Search\Service\Result\Results;
use SilverStripe\Search\Service\SearchServiceAdaptor as SearchServiceAdaptorInterface;
use SilverStripe\SearchElastic\Helpers\QueryParamsHelper;

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
        $params = QueryParamsHelper::getQueryParams($query);
        $engine = $this->environmentizeIndex($indexName);
        $request = new Search($engine, $params);
        $response = $this->client->appSearch()->search($request);

        Debug::dump($response->asArray());
        return new Results($query);
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

    private function extractResults(Response $response): PaginatedList
    {
        $resultList = ArrayList::create();
        $responseArray = $resultList->toArray();

        if (!array_key_exists('results', $responseArray)) {
            throw new Exception('Elastic Response contained to results array');
        }

        foreach ($responseArray['results'] as $result) {
            // Get the DataObject ClassName and ID for lookup in the database
            $class = $result['record_base_class']['raw'];
            $id = $result['record_id']['raw'];
        }
    }

    private function findDataObject(string $className, int $id): ?DataObject
    {
        if (!$className || !$id) {
            return null;
        }

        return DataObject::get($className)->byID($id);
    }

}
