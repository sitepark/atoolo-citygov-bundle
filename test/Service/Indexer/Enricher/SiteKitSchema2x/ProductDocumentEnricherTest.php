<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\Indexer\Enricher\SiteKitSchema2x;

// phpcs:ignore
use Atoolo\CityGov\Service\Indexer\Enricher\SiteKitSchema2x\OrganisationDocumentEnricher;
// phpcs:ignore
use Atoolo\CityGov\Service\Indexer\Enricher\SiteKitSchema2x\ProductDocumentEnricher;
use Atoolo\Resource\Exception\InvalidResourceException;
use Atoolo\Resource\Exception\ResourceNotFoundException;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Search\Exception\DocumentEnrichingException;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarium\Core\Query\DocumentInterface;

#[CoversClass(ProductDocumentEnricher::class)]

class ProductDocumentEnricherTest extends TestCase
{
    public function testIsIndexable(): void
    {
        $resourceLoader = $this->createStub(
            ResourceLoader::class
        );
        $organisationEnricher = $this->createStub(
            OrganisationDocumentEnricher::class
        );
        $enricher = new ProductDocumentEnricher(
            $resourceLoader,
            $organisationEnricher
        );
        $resource = $this->createMock(Resource::class);
        self::assertTrue(
            $enricher->isIndexable($resource),
            "should not mark any resource as not indexable"
        );
    }

    public function testObjectType(): void
    {
        $doc = $this->enrichDocument(
            'content',
            []
        );

        $this->assertEquals(
            [],
            get_object_vars($doc),
            'document should be empty'
        );
    }

    public function testKeywords(): void
    {
        $doc = $this->enrichDocument(
            'citygovProduct',
            [
                'metadata' => [
                    'citygovProduct' => [
                        'synonymList' => [
                            'Synonym6',
                            'Synonym7'
                        ]
                    ]
                ]
            ]
        );

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
        $doc = $this->enrichDocument(
            'citygovProduct',
            [
                'metadata' => [
                    'citygovProduct' => [
                        'name' => 'ProductName'
                    ]
                ]
            ]
        );

        $this->assertEquals(
            'P',
            $doc->sp_citygov_startletter,
            'unexpected startletter'
        );
    }

    public function testSortvalue(): void
    {
        $doc = $this->enrichDocument(
            'citygovProduct',
            [
                'metadata' => [
                    'citygovProduct' => [
                        'name' => 'ProductName'
                    ]
                ]
            ]
        );

        $this->assertEquals(
            'ProductName',
            $doc->sp_sortvalue,
            'unexpected sortvalue'
        );
    }

    public function testOrganisation(): void
    {
        $doc = $this->enrichDocument(
            'citygovProduct',
            [
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
            ]
        );

        $this->assertEquals(
            '12',
            $doc->sp_organisation,
            'unexpected organisation'
        );
    }

    public function testOrganisationWithoutPrimary(): void
    {
        $doc = $this->enrichDocument(
            'citygovProduct',
            [
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
            ]
        );

        $this->assertArrayNotHasKey(
            'sp_organisation',
            $doc->getFields(),
            'unexpected organisation'
        );
    }

    public function testOrganisationWithoutUrl(): void
    {
        $doc = $this->enrichDocument(
            'citygovProduct',
            [
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
            ]
        );

        $this->assertArrayNotHasKey(
            'sp_organisation',
            $doc->getFields(),
            'unexpected organisation'
        );
    }

    public function testOrganisationWithException(): void
    {
        $this->expectException(DocumentEnrichingException::class);
        $this->enrichDocument(
            'citygovProduct',
            [
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
            ]
        );
    }

    public function testContentTypeOnlineService(): void
    {
        $doc = $this->enrichDocument(
            'citygovProduct',
            [
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
            ]
        );

        $this->assertEquals(
            ['citygovOnlineService'],
            $doc->sp_contenttype,
            'unexpected contenttype'
        );
    }

    public function testLeikaKeys(): void
    {
        $doc = $this->enrichDocument(
            'citygovProduct',
            [
                'metadata' => [
                    'citygovProduct' => [
                        'leikaKeys' => [
                            'leika1',
                            'leika2'
                        ]
                    ]
                ]
            ]
        );

        /** @var array{sp_meta_string_leikanumber: string[]} $fields */
        $fields = $doc->getFields();
        print_r($fields);

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
        $doc = $this->enrichDocument(
            'citygovProduct',
            [
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
            ]
        );

        $this->assertEquals(
            'Information',
            $doc->content,
            'unexpected content'
        );
    }
    private function enrichDocument(
        string $objectType,
        array $data
    ): DocumentInterface {

        $resourceLoader = $this->createStub(
            ResourceLoader::class
        );
        $orga = new Resource(
            '/orga.php',
            '12',
            'orga',
            'citygovOrganisation',
            [
                'metadata' => [
                    'citygovOrganisation' => [
                        'name' => 'orgaName',
                        'token' => 'token.A',
                        'synonymList' => ['Synonym1', 'Synonym2']
                    ]
                ]
            ]
        );
        $resourceLoader->expects($this->any())
            ->method('load')
            ->willReturnCallback(function ($location) use ($orga) {
                if ($location === 'throwException') {
                    throw new InvalidResourceException(
                        'throwException',
                        'throw for test'
                    );
                }
                if ($location === '/orga.php') {
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

        $resource = new Resource(
            'test',
            'test',
            'test',
            $objectType,
            $data
        );

        return $enricher->enrichDocument($resource, $doc, '');
    }
}
