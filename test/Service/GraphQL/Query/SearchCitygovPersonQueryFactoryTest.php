<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\GraphQL\Query;

use Atoolo\CityGov\Service\GraphQL\Input\CitygovPerson;
use Atoolo\CityGov\Service\GraphQL\Input\SearchCitygovPersonInput;
use Atoolo\CityGov\Service\GraphQL\Query\CitygovPersonFilterFactory;
use Atoolo\CityGov\Service\GraphQL\Query\SearchCitygovPersonQueryFactory;
use Atoolo\GraphQL\Search\Input\InputSortCriteria;
use Atoolo\GraphQL\Search\Types\SortDirection;
use Atoolo\Search\Dto\Search\Query\Sort\Natural;
use Atoolo\Search\Dto\Search\Query\Sort\Score;
use Atoolo\Search\Dto\Search\Query\Sort\Direction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchCitygovPersonQueryFactory::class)]
class SearchCitygovPersonQueryFactoryTest extends TestCase
{
    private SearchCitygovPersonQueryFactory $queryFactory;

    public function setUp(): void
    {
        $personFilterFactory = $this->createMock(CitygovPersonFilterFactory::class);
        $this->queryFactory = new SearchCitygovPersonQueryFactory($personFilterFactory);
    }

    public function testCreateWithLimit(): void
    {
        $query = $this->queryFactory->create($this->createInput());
        $this->assertEquals(5, $query->limit);
    }

    public function testCreateWithOffset(): void
    {
        $query = $this->queryFactory->create($this->createInput());
        $this->assertEquals(15, $query->offset);
    }

    public function testCreateWithLang(): void
    {
        $query = $this->queryFactory->create($this->createInput());
        $this->assertEquals('en', $query->lang->code);
    }

    public function testCreateWithSort(): void
    {
        $query = $this->queryFactory->create($this->createInput());
        $this->assertInstanceOf(Natural::class, $query->sort[0]);
        $this->assertEquals(Direction::DESC, $query->sort[0]->direction);
        $this->assertInstanceOf(Score::class, $query->sort[1]);
        $this->assertEquals(Direction::ASC, $query->sort[1]->direction);
    }

    private function createInput(): SearchCitygovPersonInput
    {
        $input = new SearchCitygovPersonInput();
        $input->person = new CitygovPerson();
        $input->person->firstname = 'Max';
        $input->person->lastname = 'Mustermann';
        $input->person->product = 'Musterprodukt';
        $input->person->function = 'Musterfunktion';
        $input->person->organisation = 'Musterorganisation';
        $input->person->address = 'Musteraddresse';
        $input->person->phonenumber = 'Mustertelefonnummer';
        $input->lang = 'en';
        $input->offset = 15;
        $input->limit = 5;
        $sortA = new InputSortCriteria();
        $sortA->natural = SortDirection::DESC;
        $sortB = new InputSortCriteria();
        $sortB->score = SortDirection::ASC;
        $input->sort = [$sortA, $sortB];
        return $input;
    }
}
