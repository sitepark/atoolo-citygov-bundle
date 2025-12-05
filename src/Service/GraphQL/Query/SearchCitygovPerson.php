<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\GraphQL\Query;

use Overblog\GraphQLBundle\Annotation as GQL;
use Atoolo\CityGov\Service\GraphQL\Input\SearchCitygovPersonInput as SearchInput;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Overblog\GraphQLBundle\Error\UserError;

#[GQL\Provider]
class SearchCitygovPerson
{
    private readonly SearchCitygovPersonQueryFactory $queryFactory;

    public function __construct(
        private readonly \Atoolo\Search\Search $search,
    ) {
        $this->queryFactory = new SearchCitygovPersonQueryFactory();
    }

    #[GQL\Query(name: 'searchCitygovPerson', type: 'SearchResult!')]
    public function search(SearchInput $input): SearchResult
    {
        $query = $this->queryFactory->create($input);
        try {
            return $this->search->search($query);
        } catch (\Exception $e) {
            throw new UserError(
                $e->getMessage(),
                0,
                $e,
            );
        }
    }
}
