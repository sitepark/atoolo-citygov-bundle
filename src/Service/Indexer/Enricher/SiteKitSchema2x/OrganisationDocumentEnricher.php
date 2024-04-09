<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\Indexer\Enricher\SiteKitSchema2x;

use Atoolo\Resource\Loader\SiteKitResourceHierarchyLoader;
use Atoolo\Resource\Resource;
use Atoolo\Search\Exception\DocumentEnrichingException;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use Exception;

/**
 * TODO sp_vv_alternativeTitle -> https://gitlab.sitepark.com/sitekit/citygov-php/-/blob/develop/php/SP/CityGov/Component/Initialization.php?ref_type=heads#L122
 * @implements DocumentEnricher<IndexSchema2xDocument>
 */
class OrganisationDocumentEnricher implements DocumentEnricher
{
    public function __construct(
        private readonly SiteKitResourceHierarchyLoader $hierarchyLoader
    ) {
    }

    public function isIndexable(Resource $resource): bool
    {
        return true;
    }

    /**
     * @throws DocumentEnrichingException
     */
    public function enrichDocument(
        Resource $resource,
        IndexDocument $doc,
        string $processId
    ): IndexDocument {

        if ($resource->getObjectType() !== 'citygovOrganisation') {
            return $doc;
        }

        return $this->enrichDocumentForOrganisation($resource, $doc);
    }

    /**
     * @template E of IndexSchema2xDocument
     * @param E $doc
     * @return E
     */
    private function enrichDocumentForOrganisation(
        Resource $resource,
        IndexDocument $doc
    ): IndexDocument {

        /** @var string[] $synonymList */
        $synonymList = $resource->getData()->getArray(
            'metadata.citygovOrganisation.synonymList'
        );

        $doc->keywords = array_merge($doc->keywords ?? [], $synonymList);

        $name = str_replace(
            ["ä","ö","ü", "Ä","Ö","Ü"],
            ["ae", "oe", "ue", "Ae", "Oe", "Ue"],
            $resource->getData()->getString(
                'metadata.citygovOrganisation.name'
            )
        );
        $doc->sp_sortvalue = $name;
        if (!empty($name)) {
            $doc->sp_citygov_startletter = mb_substr($name, 0, 1);
        }

        $doc->sp_citygov_organisationtoken = [$resource->getData()->getString(
            'metadata.citygovOrganisation.token'
        )];

        $content = array_merge(
            [$doc->content ?? ''],
            $doc->sp_citygov_organisationtoken
        );
        $doc->content = trim(implode(' ', $content));

        return $this->enrichOrganisationPath($resource, $doc);
    }

    /**
     * @template E of IndexSchema2xDocument
     * @param E $doc
     * @return E
     */
    public function enrichOrganisationPath(
        Resource $resource,
        IndexDocument $doc
    ): IndexDocument {
        $doc->sp_organisation = (int)$resource->getId();

        try {
            $organisationPath =
                $this->hierarchyLoader->loadPrimaryPath(
                    $resource->getLocation()
                );
            $organisationIdPath = array_map(static function ($resource) {
                return (int)$resource->getId();
            }, $organisationPath);
            $doc->sp_organisation_path = array_merge(
                $doc->sp_organisation_path ?? [],
                $organisationIdPath
            );
        } catch (Exception $e) {
            throw new DocumentEnrichingException(
                $resource->getLocation(),
                'Unable to enrich sp_organisation_path',
                0,
                $e
            );
        }

        return $doc;
    }
}
