<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\Indexer\Enricher\GenAi;

use Atoolo\CityGov\Service\Indexer\Enricher\GenAi\CityGovPersonGenAiDocumentEnricher;
use Atoolo\GenAi\Dto\Indexer\Link;
use Atoolo\GenAi\Dto\Indexer\LinkSection;
use Atoolo\GenAi\Dto\Indexer\TextSection;
use Atoolo\GenAi\Service\Indexer\GenAiDocument;
use Atoolo\Resource\Exception\ResourceNotFoundException;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Resource\ResourceLocation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(CityGovPersonGenAiDocumentEnricher::class)]
class CityGovPersonGenAiDocumentEnricherTest extends TestCase
{
    public function testOrganisationsWithFunctionPrimaryFirst(): void
    {
        $doc = $this->enrichDocument([
            'membershipList' => ['items' => [
                [
                    'organisation' => ['url' => '/council.php'],
                    'function' => ['appendix' => 'Bezirksbeirat'],
                ],
                [
                    'primary' => true,
                    'organisation' => ['url' => '/gkr.php'],
                    'function' => [
                        'name' => 'Leiter / Leiterin',
                        'appendix' => 'Erster  Bürgermeister',
                    ],
                ],
            ]],
        ]);

        $this->assertEquals(
            [
                new TextSection(
                    'Organisationseinheiten',
                    '<ul><li>Geschäftskreis I – Leiter / Leiterin'
                    . ' Erster Bürgermeister</li>'
                    . '<li>Bezirksbeirat Mitte – Bezirksbeirat</li></ul>',
                ),
                new LinkSection('Organisationseinheiten', [
                    new Link('/gkr.php', 'Geschäftskreis I'),
                    new Link('/council.php', 'Bezirksbeirat Mitte'),
                ]),
            ],
            $doc->content,
            'unexpected organisation sections',
        );
    }

    public function testOrganisationWithoutNameFallsBackToResourceName(): void
    {
        $doc = $this->enrichDocument([
            'membershipList' => ['items' => [
                ['organisation' => ['url' => '/nameless.php']],
            ]],
        ]);

        $this->assertEquals(
            new TextSection(
                'Organisationseinheiten',
                '<ul><li>Stadtarchiv</li></ul>',
            ),
            $doc->content[0] ?? null,
            'the name of the resource should be used',
        );
    }

    public function testCompetences(): void
    {
        $doc = $this->enrichDocument([
            'competenceList' => ['items' => [
                [
                    'product' => ['url' => '/product.php'],
                    'criterias' => [
                        [
                            'type' => 'prefix',
                            'values' => [
                                ['from' => 'A', 'to' => 'K'],
                                ['from' => 'Q', 'to' => 'Q'],
                            ],
                        ],
                        ['type' => 'unknown', 'values' => [['from' => 'x']]],
                        ['type' => 'file', 'values' => []],
                    ],
                    'substitute' => ['name' => 'Ulrich Bayer'],
                ],
                [
                    'productName' => 'Fundsachen <Büro>',
                    'description' => 'nur montags',
                ],
                ['productName' => ' '],
            ]],
        ]);

        $this->assertEquals(
            [
                new TextSection(
                    'Zuständigkeiten',
                    '<ul><li>Wohnberechtigungsschein beantragen'
                    . ' (Buchstabenbereich A – K, Q; Vertretung: Ulrich Bayer)'
                    . '</li><li>Fundsachen &lt;Büro&gt; (nur montags)</li></ul>',
                ),
                new LinkSection('Zuständigkeiten', [
                    new Link('/product.php', 'Wohnberechtigungsschein beantragen'),
                ]),
            ],
            $doc->content,
            'unexpected competence sections',
        );
    }

    public function testSubstitutes(): void
    {
        $doc = $this->enrichDocument([
            'substituteList' => ['items' => [
                ['url' => '/person.php'],
            ]],
        ]);

        $this->assertEquals(
            [
                new TextSection('Vertretung', '<ul><li>Ulrich Bayer</li></ul>'),
                new LinkSection('Vertretung', [
                    new Link('/person.php', 'Ulrich Bayer'),
                ]),
            ],
            $doc->content,
            'unexpected substitute sections',
        );
    }

    public function testReferenceThatCannotBeLoadedIsSkipped(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $doc = $this->enrichDocument(
            [
                'membershipList' => ['items' => [
                    ['organisation' => ['url' => '/missing.php']],
                    ['organisation' => []],
                    'invalid',
                ]],
                'substituteList' => ['items' => [['url' => '']]],
            ],
            $logger,
        );

        $this->assertEmpty($doc->content, 'no section expected');
    }

    public function testOtherObjectTypesAreNotChanged(): void
    {
        $doc = new GenAiDocument();
        $this->enricher()->enrichDocument(
            Resource::create([
                'objectType' => 'citygovOrganisation',
                'metadata' => ['citygovPerson' => ['membershipList' => [
                    'items' => [['organisation' => ['url' => '/gkr.php']]],
                ]]],
            ]),
            $doc,
            'process-id',
        );

        $this->assertEmpty($doc->content, 'no section expected');
    }

    public function testMediaIsNotChanged(): void
    {
        $doc = new GenAiDocument();
        $doc->type = 'media';
        $this->enricher()->enrichDocument(
            $this->person([
                'membershipList' => ['items' => [
                    ['organisation' => ['url' => '/gkr.php']],
                ]],
            ]),
            $doc,
            'process-id',
        );

        $this->assertEmpty($doc->content, 'no section expected');
    }

    public function testCleanup(): void
    {
        $this->expectNotToPerformAssertions();
        $this->enricher()->cleanup();
    }

    /**
     * @param array<string,mixed> $person
     */
    private function enrichDocument(
        array $person,
        ?LoggerInterface $logger = null,
    ): GenAiDocument {
        $enricher = $this->enricher();
        if ($logger !== null) {
            $enricher->setLogger($logger);
        }
        /** @var GenAiDocument $doc */
        $doc = $enricher->enrichDocument(
            $this->person($person),
            new GenAiDocument(),
            'process-id',
        );
        return $doc;
    }

    /**
     * @param array<string,mixed> $person
     */
    private function person(array $person): Resource
    {
        return Resource::create([
            'objectType' => 'citygovPerson',
            'metadata' => ['citygovPerson' => $person],
        ]);
    }

    private function enricher(): CityGovPersonGenAiDocumentEnricher
    {
        $resources = [
            '/gkr.php' => [
                'name' => 'Gkr',
                'metadata' => [
                    'citygovOrganisation' => ['name' => 'Geschäftskreis I'],
                ],
            ],
            '/council.php' => [
                'metadata' => [
                    'citygovOrganisation' => ['name' => 'Bezirksbeirat Mitte'],
                ],
            ],
            '/nameless.php' => ['name' => 'Stadtarchiv'],
            '/product.php' => [
                'metadata' => [
                    'citygovProduct' => [
                        'name' => 'Wohnberechtigungsschein beantragen',
                    ],
                ],
            ],
            '/person.php' => [
                'metadata' => [
                    'citygovPerson' => [
                        'firstname' => 'Ulrich',
                        'lastname' => 'Bayer',
                    ],
                ],
            ],
        ];
        $resourceLoader = $this->createStub(ResourceLoader::class);
        $resourceLoader->method('load')->willReturnCallback(
            function (ResourceLocation $location) use ($resources) {
                $data = $resources[$location->location] ?? null;
                if ($data === null) {
                    throw new ResourceNotFoundException($location);
                }
                return Resource::create(
                    ['url' => $location->location] + $data,
                );
            },
        );
        return new CityGovPersonGenAiDocumentEnricher($resourceLoader);
    }
}
