<?php

namespace Atoolo\CityGov\Factory;

use Atoolo\CityGov\ChannelAttributes;
use Atoolo\Resource\ResourceChannel;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsAlias(id: 'atoolo_citygov.resource_channel_factory')]
class ChannelAttributesFactory
{
    public function __construct(
        #[Autowire(param: 'atoolo_resource.resource_channel')]
        private readonly ResourceChannel $resourceChannel,
    ) {}

    public function create(): ChannelAttributes
    {
        return new ChannelAttributes(
            $this->resourceChannel->attributes->getBool('sp_vv_alternativeTitle', false)
            ||
            strtolower(
                $this->resourceChannel->attributes->getString('sp_vv_alternativeTitle', 'false'),
            ) === 'true',
        );
    }
}
