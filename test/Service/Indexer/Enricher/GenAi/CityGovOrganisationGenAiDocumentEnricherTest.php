<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\Indexer\Enricher\GenAi;

use Atoolo\CityGov\Service\Indexer\Enricher\GenAi\CityGovOrganisationGenAiDocumentEnricher;
use Atoolo\CityGov\Test\TestResourceFactory;
use Atoolo\GenAi\Dto\Indexer\TextSection;
use Atoolo\GenAi\Service\Indexer\GenAiDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CityGovOrganisationGenAiDocumentEnricher::class)]
class CityGovOrganisationGenAiDocumentEnricherTest extends TestCase
{
    public function testDescriptionAndNoReferences(): void
    {
        $doc = $this->enrichDocument('citygovOrganisation', [
            'content' => [
                'description' => $this->block('<p>Wir helfen.</p>'),
                'intro' => $this->block('<p>Kurz.</p>'),
            ],
            'leaderList' => ['items' => [['url' => '/leader.php']]],
            'membershipList' => ['items' => [
                ['person' => ['url' => '/person.php']],
            ]],
            'responsibilityList' => ['items' => [
                ['product' => ['url' => '/product.php']],
            ]],
        ]);

        $this->assertEquals(
            [
                new TextSection('Kurzeinleitung', '<p>Kurz.</p>'),
                new TextSection('Beschreibung', '<p>Wir helfen.</p>'),
            ],
            $doc->content,
            'only the description should be added, no references',
        );
    }

    public function testDepartment(): void
    {
        $doc = $this->enrichDocument('citygovDepartment', [
            'content' => ['description' => $this->block('<p>Referat.</p>')],
        ]);

        $this->assertEquals(
            [new TextSection('Beschreibung', '<p>Referat.</p>')],
            $doc->content,
            'a department should be described as well',
        );
    }

    public function testOtherObjectTypesAreNotChanged(): void
    {
        $doc = $this->enrichDocument('citygovProduct', [
            'content' => ['description' => $this->block('<p>Text</p>')],
        ]);

        $this->assertEmpty($doc->content, 'unexpected sections');
    }

    public function testMediaIsNotChanged(): void
    {
        $doc = new GenAiDocument();
        $doc->type = 'media';

        (new CityGovOrganisationGenAiDocumentEnricher())->enrichDocument(
            TestResourceFactory::create([
                'objectType' => 'citygovOrganisation',
                'metadata' => ['citygovOrganisation' => ['content' => [
                    'description' => $this->block('<p>Text</p>'),
                ]]],
            ]),
            $doc,
            'process-id',
        );

        $this->assertEmpty($doc->content, 'unexpected sections');
    }

    public function testCleanup(): void
    {
        $this->expectNotToPerformAssertions();
        (new CityGovOrganisationGenAiDocumentEnricher())->cleanup();
    }

    /**
     * @param array<string,mixed> $organisation
     */
    private function enrichDocument(
        string $objectType,
        array $organisation,
    ): GenAiDocument {
        /** @var GenAiDocument $doc */
        $doc = (new CityGovOrganisationGenAiDocumentEnricher())->enrichDocument(
            TestResourceFactory::create([
                'objectType' => $objectType,
                'metadata' => ['citygovOrganisation' => $organisation],
            ]),
            new GenAiDocument(),
            'process-id',
        );
        return $doc;
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
}
