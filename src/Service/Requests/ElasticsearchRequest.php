<?php

namespace SilverStripe\DiscovererElasticEnterprise\Service\Requests;

use Elastic\EnterpriseSearch\AppSearch\Schema\EsSearchParams;
use Elastic\EnterpriseSearch\Request\Request;
use SilverStripe\Core\Injector\Injectable;

/**
 * Request to submit elasticsearch queries via the Enterprise search endpoint
 *
 * @see https://www.elastic.co/guide/en/app-search/8.17/elasticsearch-search-api-reference.html
 */
class ElasticsearchRequest extends Request
{

    use Injectable;

    public function __construct(string $engineName, EsSearchParams $params)
    {
        $this->method = 'POST';
        $engine_name = urlencode($engineName);
        $this->path = '/api/as/v1/engines/' . $engine_name . '/elasticsearch/_search';
        $this->headers['Content-Type'] = 'application/json';
        $this->body = $params;
    }

}
