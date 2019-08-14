# concrete5 `@since` tagger

When developing for multiple concrete5 versions, developers need to know when a class/interface/method/constant/... has been introduces.

The [`@since`](https://docs.phpdoc.org/references/phpdoc/tags/since.html) phpdoc tag is the standard way to document this.

The process implement requires these steps: 

1. analyze *all* the concrete5 versions (starting from 5.7.0), extracting the defined classes/interfaces/methods/constants/...
   This can be done with the `./bin/concrete5-since-tagger update` CLI command (to analyze all the concrete5 versions) and/or with the `./bin/concrete5-since-tagger parse <version>` CLI command (to analyze/reanalyze a specific concrete5 version)
2. patching the corrent development version of concrete5, comparing it with the previously parsed versions.
    This can be done with the `./bin/concrete5-since-tagger patch <path>` CLI command
 

## Requirements

1. A MySQL instance
2. PHP 7.2+ *and* PHP 5.x (PHP 5.x is required for the analysis process)
3. The `unzip` CLI command


## Setup

1. copy `.env.dist` to `.env` and personalize it
2. run `composer install`
3. run `./bin/concrete5-since-tagger orm:schema-tool:create` to intialize the database

## Typical usage

```sh
# Parse all the concrete5 versions
./bin/concrete5-since-tagger update

# Patch the development version
./bin/concrete5-since-tagger patch /path/to/concrete5
```
