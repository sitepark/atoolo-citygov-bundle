<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Test\Service\Indexer\Enricher\GenAi;

use Atoolo\CityGov\Service\Indexer\Enricher\GenAi\CityGovGenAiDocumentEnricher;
use Atoolo\GenAi\Service\Indexer\GenAiDocument;
use Atoolo\Resource\Resource;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CityGovGenAiDocumentEnricher::class)]
class CityGovGenAiDocumentEnricherTest extends TestCase
{
    public function testSynonymsOfAProduct(): void
    {
        $doc = $this->enrichDocument([
            'objectType' => 'citygovProduct',
            'metadata' => [
                'citygovProduct' => [
                    'synonymList' => ['Perso', 'Ausweis'],
                ],
            ],
        ]);

        $this->assertEquals(
            ['Perso', 'Ausweis'],
            $doc->keywords,
            'unexpected synonyms as keywords',
        );
    }

    public function testSynonymsAndAlternativeNamesOfAnOrganisation(): void
    {
        $doc = $this->enrichDocument([
            'objectType' => 'citygovOrganisation',
            'metadata' => [
                'citygovOrganisation' => [
                    'synonymList' => ['Bürgerbüro'],
                    'alternativeNameList' => ['Bürgeramt', 'Bürgerbüro'],
                ],
            ],
        ]);

        $this->assertEquals(
            ['Bürgerbüro', 'Bürgeramt'],
            $doc->keywords,
            'alternative names should be keywords, each kept once',
        );
    }

    public function testKeywordsOfOtherEnrichersAreKept(): void
    {
        $doc = new GenAiDocument();
        $doc->addKeywords('Reisepass');

        (new CityGovGenAiDocumentEnricher())->enrichDocument(
            Resource::create([
                'objectType' => 'citygovProduct',
                'metadata' => [
                    'citygovProduct' => ['synonymList' => ['Pass']],
                ],
            ]),
            $doc,
            'process-id',
        );

        $this->assertEquals(
            ['Reisepass', 'Pass'],
            $doc->keywords,
            'the synonyms should be added to the keywords already there',
        );
    }

    public function testOtherObjectTypesAreNotChanged(): void
    {
        $doc = $this->enrichDocument([
            'objectType' => 'citygovPerson',
            'metadata' => [
                'citygovPerson' => ['synonymList' => ['Chef']],
            ],
        ]);

        $this->assertEmpty($doc->keywords, 'unexpected keywords');
    }

    public function testEntriesThatAreNoStringsAreSkipped(): void
    {
        $doc = $this->enrichDocument([
            'objectType' => 'citygovProduct',
            'metadata' => [
                'citygovProduct' => ['synonymList' => ['Perso', 7, null]],
            ],
        ]);

        $this->assertEquals(['Perso'], $doc->keywords, 'unexpected keywords');
    }

    public function testCleanup(): void
    {
        $this->expectNotToPerformAssertions();
        (new CityGovGenAiDocumentEnricher())->cleanup();
    }

    /**
     * @param array<string,mixed> $data
     */
    private function enrichDocument(array $data): GenAiDocument
    {
        /** @var GenAiDocument $doc */
        $doc = (new CityGovGenAiDocumentEnricher())->enrichDocument(
            Resource::create($data),
            new GenAiDocument(),
            'process-id',
        );
        return $doc;
    }
}
