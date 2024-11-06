<?php

namespace SilverStripe\DiscovererElasticEnterprise\Service\Adaptors;

use Elastic\EnterpriseSearch\AppSearch\Request\LogClickthrough;
use Elastic\EnterpriseSearch\AppSearch\Schema\ClickParams;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Discoverer\Analytics\AnalyticsData;
use SilverStripe\Discoverer\Service\Interfaces\ProcessAnalyticsAdaptor as ProcessAnalyticsAdaptorInterface;
use Throwable;

class ProcessAnalyticsAdaptor extends BaseAdaptor implements ProcessAnalyticsAdaptorInterface
{

    public function process(AnalyticsData $analyticsData): void
    {
        $query = $analyticsData->getQueryString();
        $documentId = $analyticsData->getDocumentId();
        $requestId = $analyticsData->getRequestId();
        $engineName = $analyticsData->getEngineName();

        try {
            $params = Injector::inst()->create(ClickParams::class, $query, $documentId);

            if ($requestId) {
                $params->request_id = $requestId;
            }

            $clickThrough = Injector::inst()->create(LogClickthrough::class, $engineName, $params);
            $this->getClient()->appSearch()->logClickthrough($clickThrough);
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->getLogger()->error(sprintf('Elastic error: %s', $e->getMessage()), ['elastic' => $e]);
        }
    }

}
