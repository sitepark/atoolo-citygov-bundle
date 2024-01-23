<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\Indexer\Enricher\SiteKitSchema2x;

// phpcs:ignore
use Atoolo\CityGov\Service\Indexer\Enricher\SiteKitSchema2x\OrganisationDocumentEnricher;
use Atoolo\Resource\Loader\SiteKitResourceHierarchyLoader;
use Atoolo\Resource\Resource;
use Atoolo\Search\Exception\DocumentEnrichingException;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrganisationDocumentEnricher::class)]
class OrganisationDocumentEnricherTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testIsIndexable(): void
    {
        $hierarchyLoader = $this->createStub(
            SiteKitResourceHierarchyLoader::class
        );
        $enricher = new OrganisationDocumentEnricher(
            $hierarchyLoader
        );
        $resource = $this->createMock(Resource::class);
        self::assertTrue(
            $enricher->isIndexable($resource),
            "should not mark any resource as not indexable"
        );
    }

    /**
     * @throws Exception
     */
    public function testObjectType(): void
    {
        $doc = $this->enrichDocument(
            'content',
            []
        );

        $this->assertEquals(
            [],
            $doc->getFields(),
            'document should be empty'
        );
    }

    /**
     * @throws Exception
     */
    public function testSynonymList(): void
    {
        $doc = $this->enrichDocument(
            'citygovOrganisation',
            [
                'metadata' => [
                    'citygovOrganisation' => [
                        'synonymList' => ['blue', 'red']
                    ]
                ]
            ]
        );

        $this->assertEquals(
            ['blue', 'red'],
            $doc->keywords,
            'unexpected synonyms as keywords'
        );
    }

    /**
     * @throws Exception
     */
    public function testStartLetter(): void
    {
        $doc = $this->enrichDocument(
            'citygovOrganisation',
            [
                'metadata' => [
                    'citygovOrganisation' => [
                        'name' => 'Orga'
                    ]
                ]
            ]
        );

        $this->assertEquals(
            'O',
            $doc->sp_citygov_startletter,
            'unexpected startletter'
        );
    }

    /**
     * @throws Exception
     */
    public function testToken(): void
    {
        $doc = $this->enrichDocument(
            'citygovOrganisation',
            [
                'metadata' => [
                    'citygovOrganisation' => [
                        'token' => '123'
                    ]
                ]
            ]
        );

        $this->assertEquals(
            ['123'],
            $doc->sp_citygov_organisationtoken,
            'unexpected token'
        );
    }

    /**
     * @throws Exception
     */
    public function testSortvalue(): void
    {
        $doc = $this->enrichDocument(
            'citygovOrganisation',
            [
                'metadata' => [
                    'citygovOrganisation' => [
                        'name' => 'Orga'
                    ]
                ]
            ]
        );

        $this->assertEquals(
            'Orga',
            $doc->sp_sortvalue,
            'unexpected sortvalue'
        );
    }

    /**
     * @throws Exception
     */
    public function testOrgaId(): void
    {
        $doc = $this->enrichOrganisationPath(
            [
                'init' => [
                    'id' => 123
                ]
            ]
        );

        $this->assertEquals(
            123,
            $doc->sp_organisation,
            'unexpected id'
        );
    }

    /**
     * @throws Exception
     */
    public function testOrgaPath(): void
    {
        $doc = $this->enrichOrganisationPath(
            [
                'base' => [
                    'trees' => [
                        'citygovOrganisation' => [
                            'parents' => [
                                '12' => [
                                    'url' => '/12.php'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(
            [12, 123],
            $doc->sp_organisation_path,
            'unexpected path'
        );
    }

    public function testOrgaPathWithException(): void
    {
        $hierarchyLoader = $this->createStub(
            SiteKitResourceHierarchyLoader::class
        );
        $resource = new Resource(
            '/12.php',
            '12',
            '12',
            'citygovOrganisation',
            [
                'init' => [
                    'id' => '12'
                ]
            ]
        );

        $hierarchyLoader
            ->method('loadPath')
            ->willThrowException(new DocumentEnrichingException(
                'test',
                'test'
            ));

        $enricher = new OrganisationDocumentEnricher(
            $hierarchyLoader
        );
        $doc = $this->createMock(IndexSchema2xDocument::class);

        $this->expectException(DocumentEnrichingException::class);
        $enricher->enrichOrganisationPath($resource, $doc);
    }

    /**
     * @throws Exception
     */
    private function enrichDocument(
        string $objectType,
        array $data
    ): IndexSchema2xDocument {
        $hierarchyLoader = $this->createStub(
            SiteKitResourceHierarchyLoader::class
        );
        $enricher = new OrganisationDocumentEnricher(
            $hierarchyLoader
        );
        $doc = $this->createMock(IndexSchema2xDocument::class);

        $resource = new Resource(
            'test',
            'test',
            'test',
            $objectType,
            $data
        );

        return $enricher->enrichDocument($resource, $doc, '');
    }

    /**
     * @throws Exception
     */
    private function enrichOrganisationPath(
        array $data
    ): IndexSchema2xDocument {
        $hierarchyLoader = $this->createStub(
            SiteKitResourceHierarchyLoader::class
        );
        $resource12 = new Resource(
            '/12.php',
            '12',
            '12',
            'citygovOrganisation',
            [
                'init' => [
                    'id' => '12'
                ]
            ]
        );
        $resource123 = new Resource(
            '/123.php',
            '123',
            '123',
            'citygovOrganisation',
            array_merge(
                [
                    'init' => [
                        'id' => '123'
                    ]
                ],
                $data
            )
        );

        $hierarchyLoader
            ->method('loadPath')
            ->willReturn([$resource12, $resource123]);

        $enricher = new OrganisationDocumentEnricher(
            $hierarchyLoader
        );
        $doc = $this->createMock(IndexSchema2xDocument::class);

        return $enricher->enrichOrganisationPath($resource123, $doc);
    }
}
