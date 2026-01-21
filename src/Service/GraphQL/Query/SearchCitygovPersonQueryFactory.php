<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\GraphQL\Query;

use Atoolo\CityGov\Service\GraphQL\Input\SearchCitygovPersonInput as SearchInput;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\QueryOperator;
use Atoolo\Search\Dto\Search\Query\SearchQuery;

class SearchCitygovPersonQueryFactory
{
    public function __construct(
        private readonly CitygovPersonFilterFactory $citygovPersonFilterFactory,
    ) {}

    public function create(SearchInput $input): SearchQuery
    {
        return new SearchQuery(
            '',
            ResourceLanguage::of($input->lang),
            $input->offset ?? 0,
            $input->limit ?? 10,
            [],
            $this->citygovPersonFilterFactory->createForCitygovPersonSearch($input),
            [],
            true,
            false,
            QueryOperator::OR,
            null,
            null,
            null,
        );
    }
}
