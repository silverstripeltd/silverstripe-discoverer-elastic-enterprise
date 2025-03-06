<?php

namespace SilverStripe\DiscovererElasticEnterprise\Tests;

use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;

abstract class AbstractDiscovererElasticEnterpriseTest extends SapphireTest
{

    protected function setUp(): void
    {
        parent::setUp();

        // enable env var to apply config
        Environment::setEnv('ENTERPRISE_SEARCH_API_SEARCH_KEY', 'dummy-key');
    }

}
