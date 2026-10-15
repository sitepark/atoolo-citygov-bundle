<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\Search;

use Atoolo\CityGov\ChannelAttributes;
use Atoolo\CityGov\Service\Search\CityGovResourceFactory;
use Atoolo\Resource\DataBag;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLoader;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Solarium\QueryType\Select\Result\Document;

#[CoversClass(CityGovResourceFactory::class)]
class CityGovResourceFactoryTest extends TestCase
{
    private ResourceLoader|Stub $resourceLoader;

    protected function setUp(): void
    {
        $this->resourceLoader = $this->createStub(ResourceLoader::class);
    }

    private function createFactory(bool $addAlternativeDocuments = false): CityGovResourceFactory
    {
        return new CityGovResourceFactory(
            $this->resourceLoader,
            new ChannelAttributes($addAlternativeDocuments),
        );
    }

    public function testAccept(): void
    {
        $factory = $this->createFactory();
        $document = $this->createDocument('/test.php');
        $this->assertTrue(
            $factory->accept($document, ResourceLanguage::default()),
            'should be accepted',
        );
    }

    public function testAcceptWithQueryString(): void
    {
        $factory = $this->createFactory();
        $document = $this->createDocument('/test.php?cg_at_id=1');
        $this->assertTrue(
            $factory->accept($document, ResourceLanguage::default()),
            'should be accepted with query string',
        );
    }

    public function testAcceptWithoutUrl(): void
    {
        $factory = $this->createFactory();
        $document = $this->createStub(Document::class);
        $this->assertFalse(
            $factory->accept($document, ResourceLanguage::default()),
            'should not be accepted',
        );
    }

    public function testAcceptWithWrongUrl(): void
    {
        $factory = $this->createFactory();
        $document = $this->createDocument('/test.txt');
        $this->assertFalse(
            $factory->accept($document, ResourceLanguage::default()),
            'should not be accepted',
        );
    }

    public function testCreate(): void
    {
        $factory = $this->createFactory();
        $document = $this->createDocument('/test.php');
        $resource = $this->createStub(Resource::class);
        $this->resourceLoader
            ->method('load')
            ->willReturn($resource);

        $this->assertSame(
            $resource,
            $factory->create($document, ResourceLanguage::of('de')),
            'unexpected resource',
        );
    }

    public function testCreateWithoutUrl(): void
    {
        $factory = $this->createFactory();
        $document = $this->createStub(Document::class);
        $this->expectException(LogicException::class);
        $factory->create($document, ResourceLanguage::of('de'));
    }

    public function testCreateWithAlternativeTitle(): void
    {
        $factory = $this->createFactory(addAlternativeDocuments: true);

        $resource = $this->createResourceWithAlternativeNames(
            ['Alpha', 'Beta', 'Gamma'],
        );
        $this->resourceLoader->method('load')->willReturn($resource);

        $result = $factory->create(
            $this->createDocument('/test.php?cg_at_id=1'),
            ResourceLanguage::of('de'),
        );

        $this->assertEquals('Beta', $result->data->getString('metadata.headline'));
        $this->assertEquals('Beta', $result->data->getString('base.teaser.headline'));
    }

    public function testCreateWithAlternativeTitleKeepsOtherData(): void
    {
        $factory = $this->createFactory(addAlternativeDocuments: true);

        $resource = $this->createResourceWithAlternativeNames(['Alpha']);
        $this->resourceLoader->method('load')->willReturn($resource);

        $result = $factory->create(
            $this->createDocument('/test.php?cg_at_id=0'),
            ResourceLanguage::of('de'),
        );

        $this->assertEquals(
            $resource->location,
            $result->location,
            'location should be unchanged',
        );
        $this->assertEquals(
            $resource->id,
            $result->id,
            'id should be unchanged',
        );
    }

    public function testCreateWithoutAlternativeDocumentsIgnoresParam(): void
    {
        $factory = $this->createFactory(addAlternativeDocuments: false);

        $resource = $this->createResourceWithAlternativeNames(['Alpha']);
        $this->resourceLoader->method('load')->willReturn($resource);

        $result = $factory->create(
            $this->createDocument('/test.php?cg_at_id=0'),
            ResourceLanguage::of('de'),
        );

        $this->assertSame($resource, $result, 'resource should be unchanged');
    }

    public function testCreateWithoutAtParam(): void
    {
        $factory = $this->createFactory(addAlternativeDocuments: true);

        $resource = $this->createResourceWithAlternativeNames(['Alpha']);
        $this->resourceLoader->method('load')->willReturn($resource);

        $result = $factory->create(
            $this->createDocument('/test.php'),
            ResourceLanguage::of('de'),
        );

        $this->assertSame($resource, $result, 'resource should be unchanged');
    }

    public function testCreateWithNonStringEntry(): void
    {
        $factory = $this->createFactory(addAlternativeDocuments: true);

        $resource = $this->createResourceWithAlternativeNames([]);
        $resource = new \Atoolo\Resource\Resource(
            location: '/test.php',
            url: '/test.php',
            id: 'test-id',
            name: 'Test',
            objectType: 'content',
            lang: \Atoolo\Resource\ResourceLanguage::of('de'),
            data: new \Atoolo\Resource\DataBag([
                'metadata' => [
                    'citygovProduct' => [
                        'alternativeNameList' => [42, true],
                    ],
                ],
            ]),
        );
        $this->resourceLoader->method('load')->willReturn($resource);

        $result = $factory->create(
            $this->createDocument('/test.php?cg_at_id=0'),
            \Atoolo\Resource\ResourceLanguage::of('de'),
        );

        $this->assertSame($resource, $result, 'resource should be unchanged for non-string entry');
    }

    public function testCreateWithOutOfBoundsIndex(): void
    {
        $factory = $this->createFactory(addAlternativeDocuments: true);

        $resource = $this->createResourceWithAlternativeNames(['Alpha']);
        $this->resourceLoader->method('load')->willReturn($resource);

        $result = $factory->create(
            $this->createDocument('/test.php?cg_at_id=99'),
            ResourceLanguage::of('de'),
        );

        $this->assertSame($resource, $result, 'resource should be unchanged');
    }

    private function createDocument(string $url): Document
    {
        $document = $this->createStub(Document::class);
        $document
            ->method('getFields')
            ->willReturn(['url' => $url]);
        return $document;
    }

    /**
     * @param string[] $names
     */
    private function createResourceWithAlternativeNames(array $names): Resource
    {
        return new Resource(
            location: '/test.php',
            url: '/test.php',
            id: 'test-id',
            name: 'Test',
            objectType: 'content',
            lang: ResourceLanguage::of('de'),
            data: new DataBag([
                'metadata' => [
                    'headline' => 'Original Headline',
                    'citygovProduct' => [
                        'alternativeNameList' => $names,
                    ],
                ],
                'base' => [
                    'teaser' => [
                        'title' => 'Original Teaser Title',
                    ],
                ],
            ]),
        );
    }
}
