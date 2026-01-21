<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\GraphQL\Input;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @codeCoverageIgnore
 */
#[GQL\Input]
class SuggestCitygovPersonInput
{
    #[GQL\Field(type: "CitygovPerson")]
    public ?CitygovPerson $person = null;

    #[GQL\Field(type: "CitygovPersonCompetence")]
    public ?CitygovPersonCompetence $personCompetence = null;

    #[GQL\Field(type: "Int")]
    public ?int $limit = null;

    #[GQL\Field(type: "String")]
    public ?string $lang = null;
}
