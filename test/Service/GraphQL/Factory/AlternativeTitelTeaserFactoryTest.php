<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\GraphQL\Factory;

use Atoolo\CityGov\Service\GraphQL\Factory\AlternativeTitelTeaserFactory;
use Atoolo\GraphQL\Search\Factory\LinkFactory;
use Atoolo\GraphQL\Search\Types\ArticleTeaser;
use Atoolo\GraphQL\Search\Types\Link;
use Atoolo\Resource\DataBag;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AlternativeTitelTeaserFactory::class)]
class AlternativeTitelTeaserFactoryTest extends TestCase
{
    private LinkFactory&MockObject $linkFactory;
    private AlternativeTitelTeaserFactory $factory;
    private Link $link;

    protected function setUp(): void
    {
        $this->linkFactory = $this->createMock(LinkFactory::class);
        $this->factory = new AlternativeTitelTeaserFactory($this->linkFactory);
        $this->link = new Link('/test');
    }

    public function testCreateUsesAlternativeUrlForLink(): void
    {
        $alternativeUrl = '/product.php?cg_at_id=1';
        $resource = $this->createResource([
            'url' => $alternativeUrl,
            'base' => ['teaser' => ['headline' => 'Alternative Titel']],
        ]);

        $this->linkFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                fn(Resource $r) => $r->location === $alternativeUrl,
            ))
            ->willReturn($this->link);

        $this->factory->create($resource);
    }

    public function testCreateUsesOriginalResourceWhenNoUrl(): void
    {
        $resource = $this->createResource([
            'base' => ['teaser' => ['headline' => 'Titel']],
        ]);

        $this->linkFactory
            ->expects($this->once())
            ->method('create')
            ->with($resource)
            ->willReturn($this->link);

        $this->factory->create($resource);
    }

    public function testCreateReturnsArticleTeaserWithHeadline(): void
    {
        $resource = $this->createResource([
            'base' => ['teaser' => ['headline' => 'Mein Titel']],
        ]);
        $this->linkFactory->method('create')->willReturn($this->link);

        $result = $this->factory->create($resource);

        $this->assertInstanceOf(ArticleTeaser::class, $result);
        $this->assertEquals('Mein Titel', $result->headline);
    }

    public function testCreateFallsBackToResourceNameForHeadline(): void
    {
        $resource = new Resource(
            location: '/product.php',
            id: 'id',
            name: 'Resource Name',
            objectType: 'citygovProduct',
            lang: ResourceLanguage::default(),
            data: new DataBag([]),
        );
        $this->linkFactory->method('create')->willReturn($this->link);

        $result = $this->factory->create($resource);

        $this->assertInstanceOf(ArticleTeaser::class, $result);
        $this->assertEquals('Resource Name', $result->headline);
    }

    public function testCreateWithText(): void
    {
        $resource = $this->createResource([
            'base' => ['teaser' => ['text' => 'Teaser Text']],
        ]);
        $this->linkFactory->method('create')->willReturn($this->link);

        $result = $this->factory->create($resource);

        $this->assertInstanceOf(ArticleTeaser::class, $result);
        $this->assertEquals('Teaser Text', $result->text);
    }

    public function testCreateWithEmptyTextReturnsNull(): void
    {
        $resource = $this->createResource([]);
        $this->linkFactory->method('create')->willReturn($this->link);

        $result = $this->factory->create($resource);

        $this->assertInstanceOf(ArticleTeaser::class, $result);
        $this->assertNull($result->text);
    }

    public function testCreatePassesOriginalResourceToTeaser(): void
    {
        $resource = $this->createResource([
            'url' => '/product.php?cg_at_id=1',
        ]);
        $this->linkFactory->method('create')->willReturn($this->link);

        $result = $this->factory->create($resource);

        $this->assertInstanceOf(ArticleTeaser::class, $result);
        $this->assertSame($resource, $result->resource);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function createResource(array $data): Resource
    {
        return new Resource(
            location: '/product.php',
            id: 'test-id',
            name: 'Test Product',
            objectType: 'citygovProduct',
            lang: ResourceLanguage::default(),
            data: new DataBag($data),
        );
    }
}
