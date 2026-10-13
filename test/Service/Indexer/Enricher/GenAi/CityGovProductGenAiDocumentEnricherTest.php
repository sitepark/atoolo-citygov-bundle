<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\Indexer\Enricher\GenAi;

use Atoolo\CityGov\Service\Indexer\Enricher\GenAi\CityGovProductGenAiDocumentEnricher;
use Atoolo\CityGov\Test\TestResourceFactory;
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

#[CoversClass(CityGovProductGenAiDocumentEnricher::class)]
class CityGovProductGenAiDocumentEnricherTest extends TestCase
{
    public function testContentBlocksInTheOrderOfTheWebsite(): void
    {
        $doc = $this->enrichDocument([
            'content' => [
                'rates' => $this->block('<p>46 Euro</p>'),
                'documents' => $this->block('<ul><li>Geburtsurkunde</li></ul>'),
                'info' => $this->block('<p>Jeder Deutsche ab 16.</p>'),
            ],
        ]);

        $this->assertEquals(
            [
                new TextSection('Beschreibung', '<p>Jeder Deutsche ab 16.</p>'),
                new TextSection(
                    'Dokumente',
                    '<ul><li>Geburtsurkunde</li></ul>',
                ),
                new TextSection('Gebühren', '<p>46 Euro</p>'),
            ],
            $doc->content,
            'unexpected content sections',
        );
    }

    public function testHeadlinesOfTheProject(): void
    {
        $doc = $this->enrichDocument(
            ['content' => [
                'documents' => $this->block('<p>Pass</p>'),
                'rates' => $this->block('<p>46 Euro</p>'),
            ]],
            ['documents' => 'Benötigte Unterlagen'],
        );

        $this->assertEquals(
            [
                new TextSection('Benötigte Unterlagen', '<p>Pass</p>'),
                new TextSection('Gebühren', '<p>46 Euro</p>'),
            ],
            $doc->content,
            'the headline of the project should replace the default',
        );
    }

    public function testResponsibleOrganisationsPrimaryFirst(): void
    {
        $doc = $this->enrichDocument([
            'responsibilityList' => ['items' => [
                ['organisation' => ['url' => '/muenster.php']],
                ['organisation' => ['url' => '/missing.php']],
                ['primary' => true, 'organisation' => ['url' => '/ost.php']],
                'kein Eintrag',
            ]],
        ]);

        $this->assertEquals(
            [
                new TextSection(
                    'Zuständige Stellen',
                    '<ul><li>Bürgerbüro Ost</li>'
                    . '<li>Bürgerbüro Münster</li></ul>',
                ),
                new LinkSection('Zuständige Stellen', [
                    new Link('/ost.php', 'Bürgerbüro Ost'),
                    new Link('/muenster.php', 'Bürgerbüro Münster'),
                ]),
            ],
            $doc->content,
            'unexpected responsible organisations',
        );
    }

    public function testOtherObjectTypesAreNotChanged(): void
    {
        /** @var GenAiDocument $doc */
        $doc = $this->enricher()->enrichDocument(
            TestResourceFactory::create([
                'objectType' => 'citygovOrganisation',
                'metadata' => ['citygovProduct' => ['content' => [
                    'info' => $this->block('<p>Text</p>'),
                ]]],
            ]),
            new GenAiDocument(),
            'process-id',
        );

        $this->assertEmpty($doc->content, 'unexpected sections');
    }

    public function testMediaIsNotChanged(): void
    {
        $doc = new GenAiDocument();
        $doc->type = 'media';
        $this->enricher()->enrichDocument(
            $this->product(['content' => [
                'info' => $this->block('<p>Text</p>'),
            ]]),
            $doc,
            'process-id',
        );

        $this->assertEmpty($doc->content, 'unexpected sections');
    }

    public function testCleanup(): void
    {
        $this->expectNotToPerformAssertions();
        $this->enricher()->cleanup();
    }

    /**
     * @param array<string,mixed> $product
     * @param array<string,string> $headlines
     */
    private function enrichDocument(
        array $product,
        array $headlines = [],
    ): GenAiDocument {
        /** @var GenAiDocument $doc */
        $doc = $this->enricher($headlines)->enrichDocument(
            $this->product($product),
            new GenAiDocument(),
            'process-id',
        );
        return $doc;
    }

    /**
     * @param array<string,mixed> $product
     */
    private function product(array $product): Resource
    {
        return TestResourceFactory::create([
            'objectType' => 'citygovProduct',
            'metadata' => ['citygovProduct' => $product],
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function block(string $html): array
    {
        return ['contentList' => [[
            'modelType' => 'content.text',
            'richText' => ['text' => $html],
        ]]];
    }

    /**
     * @param array<string,string> $headlines
     */
    private function enricher(
        array $headlines = [],
    ): CityGovProductGenAiDocumentEnricher {
        $resources = [
            '/ost.php' => [
                'metadata' => [
                    'citygovOrganisation' => ['name' => 'Bürgerbüro Ost'],
                ],
            ],
            '/muenster.php' => ['name' => 'Bürgerbüro Münster'],
        ];
        $resourceLoader = $this->createStub(ResourceLoader::class);
        $resourceLoader->method('load')->willReturnCallback(
            function (ResourceLocation $location) use ($resources) {
                $data = $resources[$location->location] ?? null;
                if ($data === null) {
                    throw new ResourceNotFoundException($location);
                }
                return TestResourceFactory::create(
                    ['url' => $location->location] + $data,
                );
            },
        );
        return new CityGovProductGenAiDocumentEnricher(
            $resourceLoader,
            $headlines,
        );
    }
}
