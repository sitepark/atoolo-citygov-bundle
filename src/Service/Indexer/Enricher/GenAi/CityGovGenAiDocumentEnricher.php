<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\Indexer\Enricher\GenAi;

use Atoolo\GenAi\Service\Indexer\GenAiDocument;
use Atoolo\Index\Service\Indexer\DocumentEnricher;
use Atoolo\Index\Service\Indexer\IndexDocument;
use Atoolo\Resource\Resource;

/**
 * Adds the synonyms and the alternative names of services and organisations
 * to the keywords of the GenAI index, so that a question finds them by a term
 * their text does not contain.
 *
 * The Solr index turns the alternative names into documents of their own for
 * the A-Z list. The GenAI index would only hold the same text twice, so they
 * are keywords here, like the synonyms.
 *
 * @implements DocumentEnricher<GenAiDocument>
 */
class CityGovGenAiDocumentEnricher implements DocumentEnricher
{
    private const OBJECT_TYPES = ['citygovProduct', 'citygovOrganisation'];

    public function cleanup(): void {}

    public function enrichDocument(
        Resource $resource,
        IndexDocument $doc,
        string $processId,
    ): IndexDocument {
        if (!in_array($resource->objectType, self::OBJECT_TYPES, true)) {
            return $doc;
        }

        foreach (['synonymList', 'alternativeNameList'] as $list) {
            $doc->addKeywords(
                ...array_values(array_filter(
                    $resource->data->getArray(
                        'metadata.' . $resource->objectType . '.' . $list,
                    ),
                    'is_string',
                )),
            );
        }

        return $doc;
    }
}
