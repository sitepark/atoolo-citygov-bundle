<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\Indexer\Enricher\GenAi;

use Atoolo\GenAi\Service\Indexer\GenAiDocument;
use Atoolo\Index\Service\Indexer\DocumentEnricher;
use Atoolo\Index\Service\Indexer\IndexDocument;
use Atoolo\Resource\Resource;

/**
 * Adds the description of an organisation to the GenAI index: its content
 * blocks (see {@see ContentBlockSections}).
 *
 * The persons, leaders and services of an organisation are references to
 * resources of their own, which are indexed themselves - a service names the
 * organisations responsible for it, a person the organisations it belongs
 * to. An organisation only carries what is its own.
 *
 * @implements DocumentEnricher<GenAiDocument>
 */
class CityGovOrganisationGenAiDocumentEnricher implements DocumentEnricher
{
    /**
     * A department is an organisation of its own type.
     */
    private const OBJECT_TYPES = ['citygovOrganisation', 'citygovDepartment'];

    /**
     * The blocks in the order of the website, with the headlines of the
     * citygov module.
     */
    private const HEADLINES = [
        'intro' => 'Kurzeinleitung',
        'description' => 'Beschreibung',
    ];

    public function cleanup(): void {}

    public function enrichDocument(
        Resource $resource,
        IndexDocument $doc,
        string $processId,
    ): IndexDocument {
        if (
            !in_array($resource->objectType, self::OBJECT_TYPES, true)
            || $doc->isMedia()
        ) {
            return $doc;
        }

        foreach (
            ContentBlockSections::of(
                $resource->data->getArray(
                    'metadata.citygovOrganisation.content',
                ),
                self::HEADLINES,
            ) as $section
        ) {
            $doc->content[] = $section;
        }

        return $doc;
    }
}
