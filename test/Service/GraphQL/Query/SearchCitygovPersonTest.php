<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\GraphQL\Query;

use Atoolo\CityGov\Service\GraphQL\Input\SearchCitygovPersonInput;
use Atoolo\CityGov\Service\GraphQL\Query\SearchCitygovPerson;
use Overblog\GraphQLBundle\Error\UserError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchCitygovPerson::class)]
class SearchCitygovPersonTest extends TestCase
{
    private \Atoolo\Search\Search&MockObject $searcher;

    public function setUp(): void
    {
        $this->searcher = $this->createMock(\Atoolo\Search\Search::class);
    }

    public function testIfSearcherIsInvoked(): void
    {
        $search = new SearchCitygovPerson($this->searcher);
        $this->searcher
            ->expects($this->once())
            ->method('search');
        $search->search(new SearchCitygovPersonInput());
    }

    public function testUserError(): void
    {
        $search = new SearchCitygovPerson($this->searcher);
        $this->searcher
            ->expects($this->once())
            ->method('search')
            ->willThrowException(new \Exception('Some user error'));
        $this->expectException(UserError::class);
        $search->search(new SearchCitygovPersonInput());
    }
}
