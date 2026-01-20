<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\GraphQL\Query;

use Atoolo\CityGov\Service\GraphQL\Input\CitygovPersonField;
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

    #[GQL\Query(name: 'suggestCitygovPersonField', type: 'SuggestResult!')]
    public function suggestCitygovPersonField(CitygovPersonField $field, SuggestInput $input): SuggestResult
    {
        return match ($field) {
            CitygovPersonField::FIRSTNAME => $this->firstnameSuggest->suggest(
                $this->queryFactory->createForFirstname($input),
            ),
            CitygovPersonField::LASTNAME => $this->lastnameSuggest->suggest(
                $this->queryFactory->createForLastname($input),
            ),
            CitygovPersonField::PRODUCT => $this->productSuggest->suggest(
                $this->queryFactory->createForProduct($input),
            ),
            CitygovPersonField::FUNCTION => $this->functionSuggest->suggest(
                $this->queryFactory->createForFunction($input),
            ),
            CitygovPersonField::ORGANISATION => $this->organisationSuggest->suggest(
                $this->queryFactory->createForOrganisation($input),
            ),
            CitygovPersonField::PHONENUMBER => $this->phonenumberSuggest->suggest(
                $this->queryFactory->createForPhonenumber($input),
            ),
            CitygovPersonField::ADDRESS => $this->addressSuggest->suggest(
                $this->queryFactory->createForAddress($input),
            ),
        };
    }
}
