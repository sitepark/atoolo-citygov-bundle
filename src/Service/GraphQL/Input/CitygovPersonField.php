<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\GraphQL\Input;

enum CitygovPersonField: string
{
    case FIRSTNAME = 'firstname';
    case LASTNAME = 'lastname';
    case PRODUCT = 'product';
    case FUNCTION = 'function';
    case ORGANISATION = 'organisation';
    case ADDRESS = 'address';
    case PHONENUMBER = 'phonenumber';
}
