<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\Indexer\Enricher\SiteKitSchema2x;

// phpcs:ignore
use Atoolo\CityGov\Service\Indexer\Enricher\SiteKitSchema2x\ContactPointDocumentEnricher;
use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContactPointDocumentEnricher::class)]
class ContactPointDocumentEnricherTest extends TestCase
{
    /**
     * @throws Exception
     */
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
    public function testPhone(): void
    {
        $doc = $this->enrichDocument(
            'citygovOrganisation',
            [
                'metadata' => [
                    'contactPoint' => [
                        'contactData' => [
                            'phoneList' => [
                                [
                                    'phone' => [
                                        'nationalNumber' => '123'
                                    ]
                                ],
                                [
                                    'phone' => [
                                        'nationalNumber' => '456'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(
            ['123', '456'],
            $doc->sp_citygov_phone,
            'unexpected phones'
        );
    }

    /**
     * @throws Exception
     */
    public function testEmail(): void
    {
        $doc = $this->enrichDocument(
            'citygovPerson',
            [
                'metadata' => [
                    'contactPoint' => [
                        'contactData' => [
                            'emailList' => [
                                [
                                    'email' => 'a@b.de'
                                ],
                                [
                                    'email' => 'c@d.de'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(
            ['a@b.de', 'c@d.de'],
            $doc->sp_citygov_email,
            'unexpected emails'
        );
    }

    /**
     * @throws Exception
     */
    public function testAddress(): void
    {
        $doc = $this->enrichDocument(
            'citygovOrganisation',
            [
                'metadata' => [
                    'contactPoint' => [
                        'addressData' => [
                            'buildingName' => 'Building',
                            'street' => 'Street',
                            'housenumber' => '10'
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(
            'Building Street 10',
            $doc->sp_citygov_address,
            'unexpected address'
        );
    }

    /**
     * @throws Exception
     */
    private function enrichDocument(
        string $objectType,
        array $data
    ): IndexSchema2xDocument {
        $enricher = new ContactPointDocumentEnricher();
        $doc = $this->createMock(IndexSchema2xDocument::class);

        $resource = new Resource(
            'test',
            'test',
            'test',
            $objectType,
            '',
            $data
        );

        return $enricher->enrichDocument($resource, $doc, '');
    }
}
