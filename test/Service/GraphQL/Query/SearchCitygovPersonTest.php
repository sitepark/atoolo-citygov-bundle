<?php

namespace Atoolo\CityGov\Test\Service\GraphQL\Query;

use Atoolo\CityGov\Service\GraphQL\Input\SearchCitygovPersonInput;
use Atoolo\CityGov\Service\GraphQL\Input\SearchCitygovPersonInput as SearchInput;
use Atoolo\CityGov\Service\GraphQL\Query\SearchCitygovPerson;
use Atoolo\CityGov\Service\GraphQL\Query\SearchCitygovPersonQueryFactory;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\Search;
use Overblog\GraphQLBundle\Error\UserError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchCitygovPerson::class)]
class SearchCitygovPersonTest extends TestCase
{
    private Search&MockObject $search;
    private SearchCitygovPersonQueryFactory&MockObject $queryFactory;
    private SearchCitygovPerson $searchCitygovPerson;

    protected function setUp(): void
    {
        $this->search = $this->createMock(Search::class);
        $this->queryFactory = $this->createMock(SearchCitygovPersonQueryFactory::class);
        $this->searchCitygovPerson = new SearchCitygovPerson(
            $this->search,
            $this->queryFactory,
        );
    }

    public function testSearchReturnsResult(): void
    {
        $input = $this->createMock(SearchInput::class);
        $query = $this->createMock(SearchQuery::class);
        $result = $this->createMock(SearchResult::class);
        $this->queryFactory->expects($this->once())
            ->method('create')
            ->with($input)
            ->willReturn($query);
        $this->search->expects($this->once())
            ->method('search')
            ->with($query)
            ->willReturn($result);

        $actualResult = $this->searchCitygovPerson->search($input);
        $this->assertSame($result, $actualResult, 'Should return the search result');
    }

    public function testUserError(): void
    {
        $this->search
            ->expects($this->once())
            ->method('search')
            ->willThrowException(new \Exception('Some user error'));
        $this->expectException(UserError::class);
        $this->searchCitygovPerson->search(new SearchCitygovPersonInput());
    }
}
