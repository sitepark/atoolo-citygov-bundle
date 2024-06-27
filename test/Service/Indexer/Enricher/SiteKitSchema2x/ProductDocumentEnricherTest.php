<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\Indexer\Enricher\SiteKitSchema2x;

// phpcs:ignore
use Atoolo\CityGov\Service\Indexer\Enricher\SiteKitSchema2x\OrganisationDocumentEnricher;
// phpcs:ignore
use Atoolo\CityGov\Service\Indexer\Enricher\SiteKitSchema2x\ProductDocumentEnricher;
use Atoolo\CityGov\Test\TestResourceFactory;
use Atoolo\Resource\Exception\InvalidResourceException;
use Atoolo\Resource\Exception\ResourceNotFoundException;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Search\Exception\DocumentEnrichingException;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Solarium\Core\Query\DocumentInterface;

#[CoversClass(ProductDocumentEnricher::class)]

class ProductDocumentEnricherTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCleanup(): void
    {
        $resourceLoader = $this->createMock(
            ResourceLoader::class
        );
        $organisationEnricher = $this->createStub(
            OrganisationDocumentEnricher::class
        );
        $resourceLoader->expects($this->once())
            ->method('cleanup');

        $enricher = new ProductDocumentEnricher(
            $resourceLoader,
            $organisationEnricher
        );
        $enricher->cleanup();
    }

    public function testObjectType(): void
    {
        $doc = $this->enrichDocument([
            'objectType' => 'content',
        ]);

        $this->assertEquals(
            [],
            $doc->getFields(),
            'document should be empty'
        );
    }

    public function testKeywords(): void
    {
        $doc = $this->enrichDocument([
            'objectType' => 'citygovProduct',
            'metadata' => [
                'citygovProduct' => [
                    'synonymList' => [
                        'Synonym6',
                        'Synonym7'
                    ]
                ]
            ]
        ]);

        $this->assertEquals(
            [
                'Synonym6',
                'Synonym7'
            ],
            $doc->keywords,
            'unexpected keywords'
        );
    }

    public function testStartletter(): void
    {
        $doc = $this->enrichDocument([
            'objectType' => 'citygovProduct',
            'metadata' => [
                'citygovProduct' => [
                    'name' => 'ProductName'
                ]
            ]
        ]);

        $this->assertEquals(
            'P',
            $doc->sp_citygov_startletter,
            'unexpected startletter'
        );
    }

    public function testSortvalue(): void
    {
        $doc = $this->enrichDocument([
            'objectType' => 'citygovProduct',
            'metadata' => [
                'citygovProduct' => [
                    'name' => 'ProductName'
                ]
            ]
        ]);

        $this->assertEquals(
            'ProductName',
            $doc->sp_sortvalue,
            'unexpected sortvalue'
        );
    }

    public function testOrganisation(): void
    {
        $doc = $this->enrichDocument([
            'objectType' => 'citygovProduct',
            'metadata' => [
                'citygovProduct' => [
                    'responsibilityList' => [
                        'items' => [
                            [
                                'primary' => true,
                                'organisation' => [
                                    'url' => '/orga.php'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertEquals(
            '12',
            $doc->sp_organisation,
            'unexpected organisation'
        );
    }

    public function testOrganisationWithoutPrimary(): void
    {
        $doc = $this->enrichDocument([
            'objectType' => 'citygovProduct',
            'metadata' => [
                'citygovProduct' => [
                    'responsibilityList' => [
                        'items' => [
                            [
                                'organisation' => [
                                    'url' => '/orga.php'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertArrayNotHasKey(
            'sp_organisation',
            $doc->getFields(),
            'unexpected organisation'
        );
    }

    public function testOrganisationWithoutUrl(): void
    {
        $doc = $this->enrichDocument([
            'objectType' => 'citygovProduct',
            'metadata' => [
                'citygovProduct' => [
                    'responsibilityList' => [
                        'items' => [
                            [
                                'primary' => true,
                                'organisation' => [
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertArrayNotHasKey(
            'sp_organisation',
            $doc->getFields(),
            'unexpected organisation'
        );
    }

    public function testOrganisationWithException(): void
    {
        $this->expectException(DocumentEnrichingException::class);
        $this->enrichDocument([
            'objectType' => 'citygovProduct',
            'metadata' => [
                'citygovProduct' => [
                    'responsibilityList' => [
                        'items' => [
                            [
                                'primary' => true,
                                'organisation' => [
                                    'url' => 'throwException'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }

    public function testContentTypeOnlineService(): void
    {
        $doc = $this->enrichDocument([
            'objectType' => 'citygovProduct',
            'metadata' => [
                'citygovProduct' => [
                    'onlineServices' => [
                        'serviceList' => [
                            [
                                'dummy' => 'value'
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertEquals(
            ['citygovOnlineService'],
            $doc->sp_contenttype,
            'unexpected contenttype'
        );
    }

    public function testLeikaKeys(): void
    {
        $doc = $this->enrichDocument([
            'objectType' => 'citygovProduct',
            'metadata' => [
                'citygovProduct' => [
                    'leikaKeys' => [
                        'leika1',
                        'leika2'
                    ]
                ]
            ]
        ]);

        /** @var array{sp_meta_string_leikanumber: string[]} $fields */
        $fields = $doc->getFields();

        $this->assertEquals(
            [
                'leika1',
                'leika2'
            ],
            $fields['sp_meta_string_leikanumber'],
            'unexpected leikaKeys'
        );
    }

    public function testRichTextContent(): void
    {
        $doc = $this->enrichDocument([
            'objectType' => 'citygovProduct',
            'metadata' => [
                'citygovProduct' => [
                    'content' => [
                        "info" => [
                            "modelType" => "citygov.contentBlock",
                            "id" => "info",
                            "contentList" => [[
                                "modelType" => "content.text",
                                "richText" => [
                                    "normalized" => true,
                                    "modelType" => "html.richText",
                                    "text" =>
                                        "<p><span>Information</span></p>"
                                ]
                            ]]
                        ],
                    ]
                ]
            ]
        ]);

        $this->assertEquals(
            'Information',
            $doc->content,
            'unexpected content'
        );
    }
    private function enrichDocument(
        array $data
    ): DocumentInterface {

        $resourceLoader = $this->createStub(
            ResourceLoader::class
        );
        $orga = TestResourceFactory::create([
            'url' => '/orga.php',
            'id' => '12',
            'name' => 'orga',
            'objectType' => 'citygovOrganisation',
            'metadata' => [
                'citygovOrganisation' => [
                    'name' => 'orgaName',
                    'token' => 'token.A',
                    'synonymList' => ['Synonym1', 'Synonym2']
                ]
            ]
        ]);
        $resourceLoader->expects($this->any())
            ->method('load')
            ->willReturnCallback(function ($location) use ($orga) {
                if ($location->location === 'throwException') {
                    throw new InvalidResourceException(
                        $location,
                        'throw for test'
                    );
                }
                if ($location->location === '/orga.php') {
                    return $orga;
                }
                throw new ResourceNotFoundException($location);
            });
        $organisationEnricher = $this->createStub(
            OrganisationDocumentEnricher::class
        );
        $organisationEnricher->expects($this->any())
            ->method('enrichOrganisationPath')
            ->willReturnCallback(function ($resource, $doc) {
                $doc->sp_organisation = 12;
                return $doc;
            });
        $enricher = new ProductDocumentEnricher(
            $resourceLoader,
            $organisationEnricher
        );
        $doc = new IndexSchema2xDocument();

        $resource = TestResourceFactory::create($data);

        return $enricher->enrichDocument($resource, $doc, '');
    }
}
