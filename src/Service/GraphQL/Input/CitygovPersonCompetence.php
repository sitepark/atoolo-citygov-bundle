<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\GraphQL\Input;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @codeCoverageIgnore
 */
#[GQL\Input(name: "CitygovPersonCompetence")]
class CitygovPersonCompetence
{
    #[GQL\Field(type: "String")]
    public ?string $prefix = null;

    #[GQL\Field(type: "String")]
    public ?string $tin = null;

    #[GQL\Field(type: "String")]
    public ?string $file = null;

    #[GQL\Field(type: "String")]
    public ?string $licensePlateLetter = null;

    #[GQL\Field(type: "String")]
    public ?string $licensePlateRegion = null;

    #[GQL\Field(type: "String")]
    public ?string $licensePlateNumber = null;
}
