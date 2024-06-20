<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\Indexer\Enricher\SiteKitSchema2x;

use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Resource\ResourceLocation;
use Atoolo\Search\Exception\DocumentEnrichingException;
use Atoolo\Search\Service\Indexer\ContentCollector;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use Atoolo\Search\Service\Indexer\SiteKit\RichtTextMatcher;
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

    public function cleanup(): void
    {
        $this->resourceLoader->cleanup();
    }

    /**
     * @throws DocumentEnrichingException
     */
    public function enrichDocument(
        Resource $resource,
        IndexDocument $doc,
        string $processId
    ): IndexDocument {

        if ($resource->objectType !== 'citygovProduct') {
            return $doc;
        }

        return $this->enrichDocumentForProduct($resource, $doc);
    }

    /**
     * @template E of IndexSchema2xDocument
     * @param E $doc
     * @return E
     */
    private function enrichDocumentForProduct(
        Resource $resource,
        IndexDocument $doc
    ): IndexDocument {

        /** @var string[] $synonymList */
        $synonymList = $resource->data->getArray(
            'metadata.citygovProduct.synonymList'
        );
        if (!empty($synonymList)) {
            $doc->keywords = array_merge($doc->keywords ?? [], $synonymList);
        }

        $name = str_replace(
            ["ä","ö","ü", "Ä","Ö","Ü"],
            ["ae", "oe", "ue", "Ae", "Oe", "Ue"],
            $resource->data->getString('metadata.citygovProduct.name')
        );
        if (!empty($name)) {
            $doc->sp_citygov_startletter = mb_substr($name, 0, 1);
        }
        $doc->sp_sortvalue = $name;

        try {
            $doc = $this->enrichOrganisationPath($resource, $doc);
        } catch (Exception $e) {
            throw new DocumentEnrichingException(
                $resource->toLocation(),
                'Unable to enrich organisation_path for product',
                0,
                $e
            );
        }

        $onlineServiceList = $resource->data->getArray(
            'metadata.citygovProduct.onlineServices.serviceList'
        );
        if (!empty($onlineServiceList)) {
            $doc->sp_contenttype = array_merge(
                $doc->sp_contenttype ?? [],
                ['citygovOnlineService']
            );
        }

        /** @var string[] $leikaKeys */
        $leikaKeys = $resource->data->getArray(
            'metadata.citygovProduct.leikaKeys'
        );
        if (!empty($leikaKeys)) {
            $doc->setMetaString('leikanumber', $leikaKeys);
        }

        $doc = $this->enrichContent($resource, $doc);

        return $doc;
    }

    /**
     * @template E of IndexSchema2xDocument
     * @param E $doc
     * @return E
     */
    private function enrichContent(
        Resource $resource,
        IndexDocument $doc
    ): IndexDocument {

        $contentCollector = new ContentCollector([
           new RichtTextMatcher()
        ]);

        $content = $contentCollector->collect(
            $resource->data->getArray(
                'metadata.citygovProduct.content'
            )
        );
        $cleanContent = preg_replace(
            '/\s+/',
            ' ',
            $content
        );

        $doc->content = trim(
            ($doc->content ?? '') . ' ' . $cleanContent
        );

        return $doc;
    }

    /**
     * @template E of IndexSchema2xDocument
     * @param E $doc
     * @return E
     */
    private function enrichOrganisationPath(
        Resource $resource,
        IndexDocument $doc
    ): IndexDocument {

        /** @var Responsibility[] $responsibilityList */
        $responsibilityList = $resource->data->getAssociativeArray(
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
                ResourceLocation::of(
                    $primaryOrganisationLocation,
                    $resource->lang
                )
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
