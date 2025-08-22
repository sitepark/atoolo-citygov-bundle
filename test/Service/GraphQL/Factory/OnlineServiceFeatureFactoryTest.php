<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\GraphQL\Factory;

use Atoolo\CityGov\Service\GraphQL\Factory\OnlineServiceFeatureFactory;
use Atoolo\CityGov\Service\GraphQL\Types\OnlineService;
use Atoolo\CityGov\Service\GraphQL\Types\OnlineServiceFeature;
use Atoolo\GraphQL\Search\Types\Link;
use Atoolo\Resource\DataBag;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OnlineServiceFeatureFactory::class)]
class OnlineServiceFeatureFactoryTest extends TestCase
{
    private OnlineServiceFeatureFactory $factory;

    public function setUp(): void
    {
        $this->factory = new OnlineServiceFeatureFactory();
    }

    public function testCreate()
    {
        $resource = $this->createResource([
            'metadata' => [
                'citygovProduct' => [
                    'onlineServices' => [
                        'some' => 'data',
                        'serviceList' => [
                            'items' => [[
                                'url' => '/path/to/online/service',
                            ]],
                        ],
                    ],
                ],
            ],
        ]);
        $onlineServiceFeatures = $this->factory->create($resource);
        $this->assertEquals(
            new OnlineServiceFeature(
                null,
                [new OnlineService(new Link('/path/to/online/service'))],
            ),
            $onlineServiceFeatures[0],
        );
    }

    public function testCreateFail()
    {
        $resource = $this->createResource([
            'metadata' => [
                'citygovProduct' => [
                    'no_online' => 'services',
                ],
            ],
        ]);
        $onlineServiceFeatures = $this->factory->create($resource);
        $this->assertEmpty($onlineServiceFeatures);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function createResource(array $data): Resource
    {
        return new Resource(
            $data['url'] ?? '',
            $data['id'] ?? '',
            $data['name'] ?? '',
            $data['objectType'] ?? '',
            ResourceLanguage::default(),
            new DataBag($data),
        );
    }
}
