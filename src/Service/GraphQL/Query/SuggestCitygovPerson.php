<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\GraphQL\Query;

use Overblog\GraphQLBundle\Annotation as GQL;
use Atoolo\CityGov\Service\GraphQL\Input\SuggestCitygovPersonInput as SuggestInput;
use Atoolo\Search\Dto\Search\Result\SuggestResult;

#[GQL\Provider]
class SuggestCitygovPerson
{
    public function __construct(
        private readonly \Atoolo\Search\Suggest $firstnameSuggest,
        private readonly \Atoolo\Search\Suggest $lastnameSuggest,
        private readonly \Atoolo\Search\Suggest $productSuggest,
        private readonly \Atoolo\Search\Suggest $functionSuggest,
        private readonly \Atoolo\Search\Suggest $organisationSuggest,
        private readonly \Atoolo\Search\Suggest $phonenumberSuggest,
        private readonly \Atoolo\Search\Suggest $addressSuggest,
        private readonly SuggestCitygovPersonQueryFactory $queryFactory,
    ) {}

    #[GQL\Query(name: 'suggestCitygovPersonFirstname', type: 'SuggestResult!')]
    public function suggestCitygovPersonFirstname(SuggestInput $input): SuggestResult
    {
        $query = $this->queryFactory->createForFirstname($input);
        return $this->firstnameSuggest->suggest($query);
    }

    #[GQL\Query(name: 'suggestCitygovPersonLastname', type: 'SuggestResult!')]
    public function suggestCitygovPersonLastname(SuggestInput $input): SuggestResult
    {
        $query = $this->queryFactory->createForLastname($input);
        return $this->lastnameSuggest->suggest($query);
    }

    #[GQL\Query(name: 'suggestCitygovPersonProduct', type: 'SuggestResult!')]
    public function suggestCitygovPersonProduct(SuggestInput $input): SuggestResult
    {
        $query = $this->queryFactory->createForProduct($input);
        return $this->productSuggest->suggest($query);
    }

    #[GQL\Query(name: 'suggestCitygovPersonFunction', type: 'SuggestResult!')]
    public function suggestCitygovPersonFunction(SuggestInput $input): SuggestResult
    {
        $query = $this->queryFactory->createForFunction($input);
        return $this->functionSuggest->suggest($query);
    }

    #[GQL\Query(name: 'suggestCitygovPersonOrganisation', type: 'SuggestResult!')]
    public function suggestCitygovPersonOrganisation(SuggestInput $input): SuggestResult
    {
        $query = $this->queryFactory->createForOrganisation($input);
        return $this->organisationSuggest->suggest($query);
    }

    #[GQL\Query(name: 'suggestCitygovPersonPhonenumber', type: 'SuggestResult!')]
    public function suggestCitygovPersonPhonenumber(SuggestInput $input): SuggestResult
    {
        $query = $this->queryFactory->createForPhonenumber($input);
        return $this->phonenumberSuggest->suggest($query);
    }

    #[GQL\Query(name: 'suggestCitygovPersonAddress', type: 'SuggestResult!')]
    public function suggestCitygovPersonAddress(SuggestInput $input): SuggestResult
    {
        $query = $this->queryFactory->createForAddress($input);
        return $this->addressSuggest->suggest($query);
    }
}
