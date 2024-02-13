# silverstripeltd/discoverer-elastic-enterprise

## Purpose

The purpose of this module is to provide you with the ability to perform search queries to Elastic Enterprise Search
indices through Silverstripe controllers.

## Dependencies

We have two private modules that make up our Elastic Enterprise Search integration (for performing actual searches):

* [Discoverer](https://github.com/silverstripeltd/discoverer)
  * This modules provides you with all of the searching interfaces that you will interact with in your project code.
  * The goal of this module is to be provider agnostic, so if we (for example) switch from Elasticsearch to Solr, or
    perhaps more likely, switch from Elastic App Search to Elasticsearch, then you (as a developer), shouldn't have to
    change much about how your applications interacts with the Service itself.
* [Discoverer > Elastic Enterprise](https://github.com/silverstripeltd/discoverer-elastic-enterprise)
  * (This module). Provides the adaptors so that the Service classes provided through the Discoverer module can 
    communicate with Elastic Enterprise Search APIs.

## Installation

Add the following to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:silverstripeltd/discoverer.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:silverstripeltd/discoverer-elastic-enterprise.git"
        }
    ]
}
```

Then run the following:

```shell script
composer require silverstripeltd/discoverer-elastic-enterprise
```

## Specify environment variables

The following environment variables are required for this module to function:

* `ENTERPRISE_SEARCH_ENDPOINT`
* `ENTERPRISE_SEARCH_ENGINE_PREFIX`
* `ENTERPRISE_SEARCH_API_SEARCH_KEY`

## Usage

Please see the documentation provided in (Discoverer)[https://github.com/silverstripeltd/discoverer].

As mentioned above, this module serves as an "adaptor provider" for Discoverer. Besides the installation steps above,
you shouldn't really be interacting with this module in your code.
