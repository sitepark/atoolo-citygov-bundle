<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\Indexer\Enricher\GenAi;

use Atoolo\GenAi\Dto\Indexer\Link;
use Atoolo\GenAi\Service\Indexer\GenAiDocument;
use Atoolo\Index\Service\Indexer\DocumentEnricher;
use Atoolo\Index\Service\Indexer\IndexDocument;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;

/**
 * Adds the description of a service to the GenAI index: its content blocks
 * (see {@see ContentBlockSections}) and the organisations responsible for
 * it.
 *
 * The contact point of a service names only its primary organisation, so
 * every responsible organisation is listed, the primary one first. They are
 * referenced only, see {@see ReferenceListTrait}.
 *
 * @implements DocumentEnricher<GenAiDocument>
 */
class CityGovProductGenAiDocumentEnricher implements DocumentEnricher
{
    use ReferenceListTrait;

    /**
     * The blocks in the order of the website, with the headlines of the
     * citygov module.
     */
    public const HEADLINES = [
        'intro' => 'Kurzbeschreibung',
        'info' => 'Beschreibung',
        'requirements' => 'Voraussetzungen',
        'processFlow' => 'Verfahrensablauf',
        'documents' => 'Dokumente',
        'forms' => 'Anträge / Formulare',
        'processTime' => 'Fristen',
        'editingTime' => 'Bearbeitungsdauer',
        'appointment' => 'Terminvereinbarung',
        'rates' => 'Gebühren',
        'ratesMethod' => 'Zahlungsarten',
        'furtherInformations' => 'Weiterführende Informationen',
        'justiceBasics' => 'Rechtsgrundlage',
        'justiceRemedy' => 'Rechtsbehelf',
        'niceToKnow' => 'Was sollte ich noch wissen?',
        'notice' => 'Bemerkung',
    ];

    /**
     * @var array<string,string>
     */
    private readonly array $headlines;

    /**
     * @param array<string,string> $headlines replace the headlines of
     *     single blocks, as a project names them on its website
     */
    public function __construct(
        private readonly ResourceLoader $resourceLoader,
        array $headlines = [],
    ) {
        $this->headlines = array_merge(self::HEADLINES, $headlines);
    }

    public function cleanup(): void {}

    public function enrichDocument(
        Resource $resource,
        IndexDocument $doc,
        string $processId,
    ): IndexDocument {
        if ($resource->objectType !== 'citygovProduct' || $doc->isMedia()) {
            return $doc;
        }

        foreach (
            ContentBlockSections::of(
                $resource->data->getArray('metadata.citygovProduct.content'),
                $this->headlines,
            ) as $section
        ) {
            $doc->content[] = $section;
        }

        /** @var array<mixed> $responsibilities */
        $responsibilities = $resource->data->getArray(
            'metadata.citygovProduct.responsibilityList.items',
        );
        $this->addSections(
            $doc,
            'Zuständige Stellen',
            $this->organisations($resource, $responsibilities),
        );

        return $doc;
    }

    /**
     * @param array<mixed> $responsibilities
     * @return list<array{text:string, link:?Link}>
     */
    private function organisations(
        Resource $product,
        array $responsibilities,
    ): array {
        $primary = [];
        $others = [];
        foreach ($responsibilities as $responsibility) {
            if (!is_array($responsibility)) {
                continue;
            }
            $reference = $responsibility['organisation'] ?? null;
            $organisation = $this->load($product, $reference);
            if ($organisation === null) {
                continue;
            }
            $name = $this->name($organisation, 'citygovOrganisation');
            if ($name === '') {
                continue;
            }
            $entry = ['text' => $name, 'link' => $this->link($reference, $name)];
            if (($responsibility['primary'] ?? false) === true) {
                $primary[] = $entry;
            } else {
                $others[] = $entry;
            }
        }
        return array_merge($primary, $others);
    }
}
