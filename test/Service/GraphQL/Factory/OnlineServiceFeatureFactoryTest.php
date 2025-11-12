<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\GraphQL\Factory;

use Atoolo\CityGov\Service\GraphQL\Factory\OnlineServiceFeatureFactory;
use Atoolo\CityGov\Service\GraphQL\Types\OnlineService;
use Atoolo\CityGov\Service\GraphQL\Types\OnlineServiceFeature;
use Atoolo\GraphQL\Search\Factory\LinkFactory;
use Atoolo\GraphQL\Search\Types\Link;
use Atoolo\Resource\DataBag;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(OnlineServiceFeatureFactory::class)]
class OnlineServiceFeatureFactoryTest extends TestCase
{
    private OnlineServiceFeatureFactory $factory;

    private ResourceLoader&MockObject $resourceLoader;

    private LinkFactory&MockObject $linkFactory;

    public function setUp(): void
    {
        $this->resourceLoader = $this->createMock(ResourceLoader::class);
        $this->linkFactory = $this->createMock(LinkFactory::class);
        $this->factory = new OnlineServiceFeatureFactory(
            $this->resourceLoader,
            $this->linkFactory,
        );
    }

    public function testCreate()
    {
        $onlineServiceResource = $this->createResource([]);
        $onlineServiceResourceUrl =  '/path/to/online/service';
        $onlineServiceResourceLink = new Link($onlineServiceResourceUrl);
        $resource = $this->createResource([
            'metadata' => [
                'citygovProduct' => [
                    'onlineServices' => [
                        'some' => 'data',
                        'serviceList' => [
                            'items' => [[
                                'url' => $onlineServiceResourceUrl,
                            ]],
                        ],
                    ],
                ],
            ],
        ]);
        $this->resourceLoader
            ->method('load')
            ->with($onlineServiceResourceUrl)
            ->willReturn($onlineServiceResource);
        $this->linkFactory
            ->method('create')
            ->with($onlineServiceResource)
            ->willReturn($onlineServiceResourceLink);

        $onlineServiceFeatures = $this->factory->create($resource);
        $this->assertEquals(
            new OnlineServiceFeature(
                null,
                [new OnlineService($onlineServiceResourceLink)],
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
