# 🧭 Silverstripe Discoverer > <img src="https://www.elastic.co/android-chrome-192x192.png" style="height:40px; vertical-align:middle"/> Elastic Enterprise Search

## Purpose

The purpose of this module is to provide you with the ability to perform search queries to Elastic Enterprise Search
(App Search) engines through Silverstripe controllers.

**Note:** App Search is one of the products included in Elastic Enterprise Search, the two names are currently used
interchangably in this module. This module does not currently provide support for Workplace Search (which is the
**other** product that is included in Enterprise Search).

## Installation

```shell script
composer require silverstripe/silverstripe-discoverer-elastic-enterprise
```

## Specify environment variables

The following environment variables are required for this module to function:

```
ENTERPRISE_SEARCH_ENDPOINT="https://abc123.app-search.ap-southeast-2.aws.found.io"
ENTERPRISE_SEARCH_ENGINE_PREFIX="engine-name-excluding-variant"
ENTERPRISE_SEARCH_API_SEARCH_KEY="search-abc123"
```

## Usage

Please see the documentation provided in (Discoverer)[https://github.com/silverstripeltd/silverstripe-discoverer].

As mentioned above, this module serves as an "adaptor provider" for Discoverer. Besides the installation steps and
environment variables above, you shouldn't really be interacting with this module in your code.

## Feature support

Spelling suggestions are supported using [App Search's Elasticsearch Search API](https://www.elastic.co/guide/en/app-search/8.17/elasticsearch-search-api-reference.html) (that's a lot of search) which is available on >=8.5 of Elasticsearch.
