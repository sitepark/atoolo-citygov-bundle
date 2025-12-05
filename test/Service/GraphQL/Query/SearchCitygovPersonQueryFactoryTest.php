<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\GraphQL\Query;

use Atoolo\CityGov\Service\GraphQL\Input\CitygovPerson;
use Atoolo\CityGov\Service\GraphQL\Input\SearchCitygovPersonInput;
use Atoolo\CityGov\Service\GraphQL\Query\SearchCitygovPersonQueryFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchCitygovPersonQueryFactory::class)]
class SearchCitygovPersonQueryFactoryTest extends TestCase
{
    private SearchCitygovPersonQueryFactory $queryFactory;

    public function setUp(): void
    {
        $this->queryFactory = new SearchCitygovPersonQueryFactory();
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
        return $input;
    }
}
