<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\Indexer\Enricher\SiteKitSchema2x;

use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Search\Exception\DocumentEnrichingException;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use Exception;

/**
 * TODO sp_vv_alternativeTitle -> https://gitlab.sitepark.com/sitekit/citygov-php/-/blob/develop/php/SP/CityGov/Component/Initialization.php?ref_type=heads#L122
 *
 * @phpstan-type Responsibility array{
 *       primary?: bool,
 *       organisation?:array{
 *           url?:string
 *       }
 *   }
 * @implements DocumentEnricher<IndexSchema2xDocument>
 */
class ProductDocumentEnricher implements DocumentEnricher
{
    public function __construct(
        private readonly ResourceLoader $resourceLoader,
        private readonly OrganisationDocumentEnricher $organisationEnricher
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

        if ($resource->getObjectType() !== 'citygovProduct') {
            return $doc;
        }

        return $this->enrichDocumentForProduct($resource, $doc);
    }

    private function enrichDocumentForProduct(
        Resource $resource,
        IndexSchema2xDocument $doc
    ): IndexSchema2xDocument {

        /** @var string[] $synonymList */
        $synonymList = $resource->getData()->getArray(
            'metadata.citygovProduct.synonymList'
        );
        if (!empty($synonymList)) {
            $doc->keywords = array_merge($doc->keywords ?? [], $synonymList);
        }

        $name = $resource->getData()->getString('metadata.citygovProduct.name');
        if (!empty($name)) {
            $doc->sp_citygov_startletter = mb_substr($name, 0, 1);
        }
        $doc->sp_sortvalue = $name;

        try {
            $doc = $this->enrichOrganisationPath($resource, $doc);
        } catch (Exception $e) {
            throw new DocumentEnrichingException(
                $resource->getLocation(),
                'Unable to enrich organisation_path for product',
                0,
                $e
            );
        }

        $onlineServiceList = $resource->getData()->getArray(
            'metadata.citygovProduct.onlineServices.serviceList'
        );
        if (!empty($onlineServiceList)) {
            $doc->sp_contenttype = array_merge(
                $doc->sp_contenttype ?? [],
                ['citygovOnlineService']
            );
        }

        return $doc;
    }

    private function enrichOrganisationPath(
        Resource $resource,
        IndexSchema2xDocument $doc
    ): IndexSchema2xDocument {

        /** @var Responsibility[] $responsibilityList */
        $responsibilityList = $resource->getData()->getAssociativeArray(
            'metadata.citygovProduct.responsibilityList.items'
        );
        foreach ($responsibilityList as $responsibility) {
            if (($responsibility['primary'] ?? false) !== true) {
                continue;
            }
            $primaryOrganisationLocation =
                $responsibility['organisation']['url']
                ?? null;
            if ($primaryOrganisationLocation === null) {
                continue;
            }

            $primaryOrganisationResource = $this->resourceLoader->load(
                $primaryOrganisationLocation
            );
            $doc = $this->organisationEnricher->enrichOrganisationPath(
                $primaryOrganisationResource,
                $doc
            );
            break;
        }

        return $doc;
    }
}
