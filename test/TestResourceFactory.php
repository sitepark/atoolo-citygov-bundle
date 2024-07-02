<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;

class TestResourceFactory
{
    public static function create(array $data): Resource
    {
        return new Resource(
            $data['url'] ?? '',
            $data['id'] ?? '',
            $data['name'] ?? '',
            $data['objectType'] ?? '',
            ResourceLanguage::of($data['locale'] ?? ''),
            new DataBag($data),
        );
    }
}
