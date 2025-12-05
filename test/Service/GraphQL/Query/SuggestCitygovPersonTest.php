<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\GraphQL\Query;

use Atoolo\CityGov\Service\GraphQL\Input\CitygovPersonField;
use Atoolo\CityGov\Service\GraphQL\Input\SuggestCitygovPersonInput;
use Atoolo\CityGov\Service\GraphQL\Query\SuggestCitygovPerson;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(SuggestCitygovPerson::class)]
class SuggestCitygovPersonTest extends TestCase
{
    /**
     * @var array<CitygovPersonField, \Atoolo\Search\Suggest&MockObject>
     */
    private array $suggesters = [];

    public function setUp(): void
    {
        $this->suggesters = [
            CitygovPersonField::FIRSTNAME->value => $this->createMock(\Atoolo\Search\Suggest::class),
            CitygovPersonField::LASTNAME->value => $this->createMock(\Atoolo\Search\Suggest::class),
            CitygovPersonField::PRODUCT->value => $this->createMock(\Atoolo\Search\Suggest::class),
            CitygovPersonField::FUNCTION->value => $this->createMock(\Atoolo\Search\Suggest::class),
            CitygovPersonField::ORGANISATION->value => $this->createMock(\Atoolo\Search\Suggest::class),
            CitygovPersonField::PHONENUMBER->value => $this->createMock(\Atoolo\Search\Suggest::class),
            CitygovPersonField::ADDRESS->value => $this->createMock(\Atoolo\Search\Suggest::class),
        ];
    }

    public function testSuggestCitygovPersonFirstname(): void
    {
        $this->expectSuggestCallFor(CitygovPersonField::FIRSTNAME);
        (new SuggestCitygovPerson(...array_values($this->suggesters)))
            ->suggestCitygovPersonFirstname(new SuggestCitygovPersonInput());
    }

    public function testSuggestCitygovPersonLastname(): void
    {
        $this->expectSuggestCallFor(CitygovPersonField::LASTNAME);
        (new SuggestCitygovPerson(...array_values($this->suggesters)))
            ->suggestCitygovPersonLastname(new SuggestCitygovPersonInput());
    }

    public function testSuggestCitygovPersonProduct(): void
    {
        $this->expectSuggestCallFor(CitygovPersonField::PRODUCT);
        (new SuggestCitygovPerson(...array_values($this->suggesters)))
            ->suggestCitygovPersonProduct(new SuggestCitygovPersonInput());
    }

    public function testSuggestCitygovPersonFunction(): void
    {
        $this->expectSuggestCallFor(CitygovPersonField::FUNCTION);
        (new SuggestCitygovPerson(...array_values($this->suggesters)))
            ->suggestCitygovPersonFunction(new SuggestCitygovPersonInput());
    }

    public function testSuggestCitygovPersonOrganisation(): void
    {
        $this->expectSuggestCallFor(CitygovPersonField::ORGANISATION);
        (new SuggestCitygovPerson(...array_values($this->suggesters)))
            ->suggestCitygovPersonOrganisation(new SuggestCitygovPersonInput());
    }

    public function testSuggestCitygovPersonPhonenumber(): void
    {
        $this->expectSuggestCallFor(CitygovPersonField::PHONENUMBER);
        (new SuggestCitygovPerson(...array_values($this->suggesters)))
            ->suggestCitygovPersonPhonenumber(new SuggestCitygovPersonInput());
    }

    public function testSuggestCitygovPersonAddress(): void
    {
        $this->expectSuggestCallFor(CitygovPersonField::ADDRESS);
        (new SuggestCitygovPerson(...array_values($this->suggesters)))
            ->suggestCitygovPersonAddress(new SuggestCitygovPersonInput());
    }

    private function expectSuggestCallFor(CitygovPersonField $forField): void
    {
        foreach ($this->suggesters as $key => &$searcher) {
            $invocationRule = $key === $forField->value ? $this->once() : $this->never();
            $searcher
                ->expects($invocationRule)
                ->method('suggest');
        }
    }
}
