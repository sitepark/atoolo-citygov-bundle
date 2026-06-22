<?php

namespace Atoolo\CityGov\Test\Factory;

use Atoolo\CityGov\Factory\ChannelAttributesFactory;
use Atoolo\Resource\DataBag;
use Atoolo\Resource\ResourceChannel;
use PHPUnit\Framework\TestCase;

class ChannelAttributesFactoryTest extends TestCase
{
    /**
     * Helper function
     * @param DataBag $attributes
     * @return ChannelAttributesFactory
     */
    public function getChannelWithAttributes(array $attributes): ChannelAttributesFactory
    {
        $resourceChannel = ResourceChannel::create([
            'id' => 'pub1',
            'name' => 'www',
            'anchor' => 'www',
            'serverName' => 'Webserver',
            'nature' => 'internet',
            'locale' => 'de_DE',
            'baseDir' => '/var/www/publisher/',
            'resourceDir' => '/',
            'configDir' => '/',
            'searchIndex' => 'www',
            'attributes' => $attributes,
            'tenant' => [
                'id' => 'pub1',
                'name' => 'www',
                'anchor' => 'www',
                'host' => 'localhost',
            ],
        ]);
        return new ChannelAttributesFactory($resourceChannel);
    }

    /**
     *
     */
    public function testEmpty(): void
    {
        $factory = $this->getChannelWithAttributes([]);
        $channelAttribute = $factory->create();
        $this->assertFalse($channelAttribute->addAlternativeDocuments);
    }

    /**
     *
     */
    public function testFalse(): void
    {
        $factory = $this->getChannelWithAttributes(['sp_vv_alternativeTitle' => false]);
        $channelAttribute = $factory->create();
        $this->assertFalse($channelAttribute->addAlternativeDocuments);
    }

    /**
     *
     */
    public function testTrue(): void
    {
        $factory = $this->getChannelWithAttributes(['sp_vv_alternativeTitle' => true]);
        $channelAttribute = $factory->create();
        $this->assertTrue($channelAttribute->addAlternativeDocuments);
    }
}
