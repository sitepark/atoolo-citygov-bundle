<?php

namespace Atoolo\CityGov\Test\Factory;

use Atoolo\CityGov\ChannelAttributes;
use Atoolo\CityGov\Factory\ChannelAttributesFactory;
use Atoolo\Resource\DataBag;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceTenant;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class ChannelAttributesFactoryTest extends TestCase
{
    private ChannelAttributesFactory $factory;

    /**
     * Helper function
     * @param DataBag $attributes
     * @return void
     */
    public function setChannelWithAttributes(DataBag $attributes): void
    {
        $resourceChannel = new ResourceChannel(
            'pub1',
            'www',
            'www',
            'Webserver',
            false,
            'internet',
            'de_DE',
            '/var/www/publihser/',
            '/',
            '/',
            'www',
            [],
            $attributes,
            new ResourceTenant(
                'pub1',
                'www',
                'www',
                'localhost',
                new DataBag([]),
            ),
        );
        $this->factory = new ChannelAttributesFactory($resourceChannel);
    }

    /**
     * @throws Exception
     */
    public function testEmpty(): void
    {
        $this->setChannelWithAttributes(new DataBag([]));
        $channelAttribute = $this->factory->create();
        $this->assertFalse($channelAttribute->addAlternativeDocuments);
    }

    /**
     * @throws Exception
     */
    public function testFalse(): void
    {
        $this->setChannelWithAttributes(new DataBag(['sp_vv_alternativeTitle' => false]));
        $channelAttribute = $this->factory->create();
        $this->assertFalse($channelAttribute->addAlternativeDocuments);
    }

    /**
     * @throws Exception
     */
    public function testTrue(): void
    {
        $this->setChannelWithAttributes(new DataBag(['sp_vv_alternativeTitle' => true]));
        $channelAttribute = $this->factory->create();
        $this->assertTrue($channelAttribute->addAlternativeDocuments);
    }
}
