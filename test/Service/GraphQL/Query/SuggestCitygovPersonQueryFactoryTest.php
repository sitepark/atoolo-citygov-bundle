<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\GraphQL\Query;

use Atoolo\CityGov\Service\GraphQL\Input\CitygovPerson;
use Atoolo\CityGov\Service\GraphQL\Input\SuggestCitygovPersonInput;
use Atoolo\CityGov\Service\GraphQL\Query\CitygovPersonFilterFactory;
use Atoolo\CityGov\Service\GraphQL\Query\SuggestCitygovPersonQueryFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SuggestCitygovPersonQueryFactory::class)]
class SuggestCitygovPersonQueryFactoryTest extends TestCase
{
    private SuggestCitygovPersonQueryFactory $queryFactory;

    public function setUp(): void
    {
        /** @var CitygovPersonFilterFactory $facrory */
        $factory = $this->createMock(CitygovPersonFilterFactory::class);
        $this->queryFactory = new SuggestCitygovPersonQueryFactory($factory);
    }

    public function testCreateForFirstname(): void
    {
        $query = $this->queryFactory->createForFirstname($this->createInput());
        $this->assertEquals('Max', $query->text);
    }

    public function testCreateForLastname(): void
    {
        $query = $this->queryFactory->createForLastname($this->createInput());
        $this->assertEquals('Mustermann', $query->text);
    }

    public function testCreateForProduct(): void
    {
        $query = $this->queryFactory->createForProduct($this->createInput());
        $this->assertEquals('Musterprodukt', $query->text);
    }

    public function testCreateForFunction(): void
    {
        $query = $this->queryFactory->createForFunction($this->createInput());
        $this->assertEquals('Musterfunktion', $query->text);
    }

    public function testCreateForOrganisation(): void
    {
        $query = $this->queryFactory->createForOrganisation($this->createInput());
        $this->assertEquals('Musterorganisation', $query->text);
    }

    public function testCreateForPhonenumber(): void
    {
        $query = $this->queryFactory->createForPhonenumber($this->createInput());
        $this->assertEquals('Mustertelefonnummer', $query->text);
    }

    public function testCreateForAddress(): void
    {
        $query = $this->queryFactory->createForAddress($this->createInput());
        $this->assertEquals('Musteraddresse', $query->text);
    }

    public function testCreateWithLimit(): void
    {
        $query = $this->queryFactory->createForFirstname($this->createInput());
        $this->assertEquals(5, $query->limit);
    }

    public function testCreateWithLang(): void
    {
        $query = $this->queryFactory->createForFirstname($this->createInput());
        $this->assertEquals('en', $query->lang->code);
    }

    private function createInput(): SuggestCitygovPersonInput
    {
        $input = new SuggestCitygovPersonInput();
        $input->person = new CitygovPerson();
        $input->person->firstname = 'Max';
        $input->person->lastname = 'Mustermann';
        $input->person->product = 'Musterprodukt';
        $input->person->function = 'Musterfunktion';
        $input->person->organisation = 'Musterorganisation';
        $input->person->address = 'Musteraddresse';
        $input->person->phonenumber = 'Mustertelefonnummer';
        $input->lang = 'en';
        $input->limit = 5;
        return $input;
    }
}
