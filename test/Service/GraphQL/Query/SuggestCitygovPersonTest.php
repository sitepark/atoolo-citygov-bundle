<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\GraphQL\Query;

use Atoolo\CityGov\Service\GraphQL\Input\CitygovPersonField;
use Atoolo\CityGov\Service\GraphQL\Input\SuggestCitygovPersonInput;
use Atoolo\CityGov\Service\GraphQL\Query\CitygovPersonFilterFactory;
use Atoolo\CityGov\Service\GraphQL\Query\SuggestCitygovPerson;
use Atoolo\CityGov\Service\GraphQL\Query\SuggestCitygovPersonQueryFactory;
use Atoolo\Search\Suggest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(SuggestCitygovPerson::class)]
class SuggestCitygovPersonTest extends TestCase
{
    /**
     * @var array<CitygovPersonField, Suggest&MockObject>
     */
    private array $suggesters = [];
    private SuggestCitygovPersonQueryFactory $queryFactory;

    public function setUp(): void
    {
        $factory = $this->createMock(CitygovPersonFilterFactory::class);
        $this->queryFactory = new SuggestCitygovPersonQueryFactory($factory);
        $this->suggesters = [
            CitygovPersonField::FIRSTNAME->value => $this->createMock(Suggest::class),
            CitygovPersonField::LASTNAME->value => $this->createMock(Suggest::class),
            CitygovPersonField::PRODUCT->value => $this->createMock(Suggest::class),
            CitygovPersonField::FUNCTION->value => $this->createMock(Suggest::class),
            CitygovPersonField::ORGANISATION->value => $this->createMock(Suggest::class),
            CitygovPersonField::PHONENUMBER->value => $this->createMock(Suggest::class),
            CitygovPersonField::ADDRESS->value => $this->createMock(Suggest::class),
        ];
    }

    public function testSuggestCitygovPersonFirstname(): void
    {
        $this->expectSuggestCallFor(CitygovPersonField::FIRSTNAME);
        (new SuggestCitygovPerson(...[...array_values($this->suggesters), $this->queryFactory]))
            ->suggestCitygovPersonFirstname(new SuggestCitygovPersonInput());
    }

    public function testSuggestCitygovPersonLastname(): void
    {
        $this->expectSuggestCallFor(CitygovPersonField::LASTNAME);
        (new SuggestCitygovPerson(...[...array_values($this->suggesters), $this->queryFactory]))
            ->suggestCitygovPersonLastname(new SuggestCitygovPersonInput());
    }

    public function testSuggestCitygovPersonProduct(): void
    {
        $this->expectSuggestCallFor(CitygovPersonField::PRODUCT);
        (new SuggestCitygovPerson(...[...array_values($this->suggesters), $this->queryFactory]))
            ->suggestCitygovPersonProduct(new SuggestCitygovPersonInput());
    }

    public function testSuggestCitygovPersonFunction(): void
    {
        $this->expectSuggestCallFor(CitygovPersonField::FUNCTION);
        (new SuggestCitygovPerson(...[...array_values($this->suggesters), $this->queryFactory]))
            ->suggestCitygovPersonFunction(new SuggestCitygovPersonInput());
    }

    public function testSuggestCitygovPersonOrganisation(): void
    {
        $this->expectSuggestCallFor(CitygovPersonField::ORGANISATION);
        (new SuggestCitygovPerson(...[...array_values($this->suggesters), $this->queryFactory]))
            ->suggestCitygovPersonOrganisation(new SuggestCitygovPersonInput());
    }

    public function testSuggestCitygovPersonPhonenumber(): void
    {
        $this->expectSuggestCallFor(CitygovPersonField::PHONENUMBER);
        (new SuggestCitygovPerson(...[...array_values($this->suggesters), $this->queryFactory]))
            ->suggestCitygovPersonPhonenumber(new SuggestCitygovPersonInput());
    }

    public function testSuggestCitygovPersonAddress(): void
    {
        $this->expectSuggestCallFor(CitygovPersonField::ADDRESS);
        (new SuggestCitygovPerson(...[...array_values($this->suggesters), $this->queryFactory]))
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
