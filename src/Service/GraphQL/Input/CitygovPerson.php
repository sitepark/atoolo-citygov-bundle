<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\GraphQL\Input;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @codeCoverageIgnore
 */
#[GQL\Input(name: "CitygovPerson")]
class CitygovPerson
{
    #[GQL\Field(type: "String")]
    public ?string $firstname = null;

    #[GQL\Field(type: "String")]
    public ?string $lastname = null;

    #[GQL\Field(type: "String")]
    public ?string $product = null;

    #[GQL\Field(type: "String")]
    public ?string $function = null;

    #[GQL\Field(type: "String")]
    public ?string $organisation = null;

    #[GQL\Field(type: "String")]
    public ?string $address = null;

    #[GQL\Field(type: "String")]
    public ?string $phonenumber = null;
}
