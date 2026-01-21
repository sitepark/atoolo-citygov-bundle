<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\GraphQL\Query;

use Atoolo\CityGov\Service\GraphQL\Input\SuggestCitygovPersonInput as SuggestInput;
use Atoolo\CityGov\Service\GraphQL\Input\CitygovPersonField;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\SuggestQuery;

class SuggestCitygovPersonQueryFactory
{
    public function __construct(
        private readonly CitygovPersonFilterFactory $citygovPersonFilterFactory,
    ) {}

    public function createForFirstname(SuggestInput $input): SuggestQuery
    {
        return $this->createFor($input, CitygovPersonField::FIRSTNAME);
    }

    public function createForLastname(SuggestInput $input): SuggestQuery
    {
        return $this->createFor($input, CitygovPersonField::LASTNAME);
    }

    public function createForProduct(SuggestInput $input): SuggestQuery
    {
        return $this->createFor($input, CitygovPersonField::PRODUCT);
    }

    public function createForFunction(SuggestInput $input): SuggestQuery
    {
        return $this->createFor($input, CitygovPersonField::FUNCTION);
    }

    public function createForOrganisation(SuggestInput $input): SuggestQuery
    {
        return $this->createFor($input, CitygovPersonField::ORGANISATION);
    }

    public function createForPhonenumber(SuggestInput $input): SuggestQuery
    {
        return $this->createFor($input, CitygovPersonField::PHONENUMBER);
    }

    public function createForAddress(SuggestInput $input): SuggestQuery
    {
        return $this->createFor($input, CitygovPersonField::ADDRESS);
    }

    private function createFor(SuggestInput $input, CitygovPersonField $forField): SuggestQuery
    {
        return new SuggestQuery(
            match ($forField) {
                CitygovPersonField::FIRSTNAME => $input->person?->firstname,
                CitygovPersonField::LASTNAME => $input->person?->lastname,
                CitygovPersonField::PRODUCT => $input->person?->product,
                CitygovPersonField::FUNCTION => $input->person?->function,
                CitygovPersonField::ORGANISATION => $input->person?->organisation,
                CitygovPersonField::PHONENUMBER => $input->person?->phonenumber,
                CitygovPersonField::ADDRESS => $input->person?->address,
            } ?? '',
            ResourceLanguage::of($input->lang),
            $this->citygovPersonFilterFactory->createForCitygovPersonSuggest($input),
            $input->limit ?? 10,
        );
    }
}
