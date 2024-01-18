<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\Indexer\Enricher\SiteKitSchema2x;

use Atoolo\Resource\Loader\SiteKitResourceHierarchyLoader;
use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;

/**
 * TODO
 * - sp_vv_alternativeTitle -> https://gitlab.sitepark.com/sitekit/citygov-php/-/blob/develop/php/SP/CityGov/Component/Initialization.php?ref_type=heads#L122
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

    private function enrichDocumentForOrganisation(
        Resource $resource,
        IndexSchema2xDocument $doc
    ): IndexSchema2xDocument {

        /** @var string[] $synonymList */
        $synonymList = $resource->getData()->getArray(
            'metadata.citygovOrganisation.synonymList'
        );

        $doc->keywords = array_merge($doc->keywords ?? [], $synonymList);

        $name = $resource->getData()->getString(
            'metadata.citygovOrganisation.name'
        );
        if (!empty($name)) {
            $doc->sp_citygov_startletter = mb_substr($name, 0, 1);
        }
        $doc->sp_citygov_organisationtoken = [$resource->getData()->getString(
            'metadata.citygovOrganisation.token'
        )];
        $doc->sp_sortvalue = $name;

        return $this->enrichOrganisationPath($resource, $doc);
    }

    public function enrichOrganisationPath(
        Resource $resource,
        IndexSchema2xDocument $doc
    ): IndexSchema2xDocument {
        $doc->sp_organisation = (int)$resource->getId();
        $organisationPath =
            $this->hierarchyLoader->loadPath(
                $resource->getLocation()
            );
        $organisationIdPath = array_map(static function ($resource) {
            return (int)$resource->getId();
        }, $organisationPath);
        $doc->sp_organisation_path = $organisationIdPath;

        return $doc;
    }
}
