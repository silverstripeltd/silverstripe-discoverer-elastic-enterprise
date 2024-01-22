# silverstripeltd/silverstripe-search-elastic-enterprise

## Purpose

The purpose of this module is to provide you with the ability to perform search queries to Elastic Enterprise Search
indices through Silverstripe controllers.

## Dependencies

We have two private modules that make up our Elastic Enterprise Search integration:

* [Silverstripe Search](https://github.com/silverstripeltd/silverstripe-search)
  * This modules provides you with all of the Search service interfaces that you will interact with in your project
    code.
  * The goal of this module is to be provider agnostic, so if we (for example) switch from Elastic to Solr, you (as a
    developer), shouldn't have to change much about how your applications interacts with the Service itself.
* [Silverstripe Search > Elastic Enterprise](https://github.com/silverstripeltd/silverstripe-search-elastic-enterprise)
  * (This module), which provides the adaptors so that the the Service module can communicate with Elastic Enterprise
    Search APIs.

## Installation

Add the following to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:silverstripeltd/silverstripe-search.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:silverstripeltd/silverstripe-search-elastic-enterprise.git"
        }
    ]
}
```

Then run the following:

```shell script
composer require silverstripeltd/silverstripe-search-elastic-enterprise
```

## Specify environment variables

The following environment variables are required for this module to function:

* `ENTERPRISE_SEARCH_ENDPOINT`
* `ENTERPRISE_SEARCH_ENGINE_PREFIX`
* `ENTERPRISE_SEARCH_API_SEARCH_KEY`
