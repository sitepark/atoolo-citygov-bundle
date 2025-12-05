<?php

namespace Atoolo\CityGov\Service\Search;

use Atoolo\Search\Service\Search\SolrSuggest;
use Atoolo\Search\Service\IndexName;
use Atoolo\Search\Service\Search\QueryTemplateResolver;
use Atoolo\Search\Service\Search\Schema2xFieldMapper;
use Atoolo\Search\Service\SolrClientFactory;

class SolrSuggestFactory
{
    public function __construct(
        private readonly IndexName $index,
        private readonly SolrClientFactory $clientFactory,
        private readonly Schema2xFieldMapper $schemaFieldMapper,
        private readonly QueryTemplateResolver $queryTemplateResolver,
    ) {}

    public function createForSolrField(string $solrField): SolrSuggest
    {
        return new SolrSuggest(
            $this->index,
            $this->clientFactory,
            $this->schemaFieldMapper,
            $this->queryTemplateResolver,
            $solrField,
        );
    }
}
