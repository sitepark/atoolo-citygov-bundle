<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\Indexer\Enricher\SiteKitSchema2x;

use Atoolo\CityGov\ChannelAttributes;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Resource\ResourceLocation;
use Atoolo\Search\Exception\DocumentEnrichingException;
use Atoolo\Search\Service\Indexer\ContentCollector;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use Atoolo\Search\Service\Indexer\SiteKit\RichtTextMatcher;
use Atoolo\Search\Service\Indexer\SolrIndexService;
use Atoolo\Search\Service\Indexer\SolrIndexUpdater;
use Exception;

/**
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
        private readonly ChannelAttributes $channelAttributes,
        private readonly SolrIndexService $solrIndexService,
        private readonly ResourceLoader $resourceLoader,
        private readonly OrganisationDocumentEnricher $organisationEnricher,
    ) {}

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
        string $processId,
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
        IndexDocument $doc,
    ): IndexDocument {

        $this->enrichName($resource->data->getString('metadata.citygovProduct.name'), $doc);
        $this->enrichLeikaNumber($resource->data->getArray('metadata.citygovProduct.leikaKeys'), $doc);
        $this->enrichOrganisationPath($resource, $doc);
        $this->enrichOnlineServices(
            $resource->data->getArray('metadata.citygovProduct.onlineServices.serviceList'),
            $doc,
        );

        $alternativeTitles = $resource->data->getArray('metadata.citygovProduct.alternativeNameList', []);
        if ($this->channelAttributes->alternativeTitle && count($alternativeTitles) > 0) {
            $updater = $this->solrIndexService->updater($resource->lang);
            for ($i = 0; $i < count($alternativeTitles); $i++) {
                /** @var IndexSchema2xDocument $alternativeTitleDocument */
                // $alternativeTitleDocument = $updater->createDocument();
                $alternativeTitleDocument = clone($doc);
                $name = $alternativeTitles[$i];
                $this->enrichName($name, $alternativeTitleDocument);
                $alternativeTitleDocument->keywords = null;
                $alternativeTitleDocument->description = null;
                $alternativeTitleDocument->id = $doc->id . '-' . $i;
                $alternativeTitleDocument->url = $doc->url . '?cg_at_id=' . $i;
                $updater->addDocument($alternativeTitleDocument);
            }
            $updater->update();
        }

        $this->enrichSynonyms($resource->data->getArray('metadata.citygovProduct.synonymList'), $doc);
        $doc = $this->enrichContent($resource, $doc);

        return $doc;
    }

    /**
     * @param string $name
     * @param IndexDocument $doc
     * @return void
     */
    private function enrichName($name, &$doc): void
    {
        $name = str_replace(
            ["ä", "ö", "ü", "Ä", "Ö", "Ü"],
            ["ae", "oe", "ue", "Ae", "Oe", "Ue"],
            $name,
        );
        if (!empty($name)) {
            $doc->sp_name = $name;
            $doc->sp_title = $name;
            $doc->title = $name;
            $doc->sp_citygov_startletter = mb_substr($name, 0, 1);
            $doc->sp_startletter = mb_substr($name, 0, 1);
        }
        $doc->sp_sortvalue = $name;
    }


    /**
     * @param string $name
     * @param IndexDocument $doc
     * @return void
     */
    private function enrichSynonyms($synonymList, &$doc): void
    {
        if (!empty($synonymList)) {
            $doc->keywords = array_merge($doc->keywords ?? [], $synonymList);
        }
    }

    private function enrichOnlineServices($onlineServiceList, $doc): void
    {
        if (!empty($onlineServiceList)) {
            $doc->sp_contenttype = array_merge(
                $doc->sp_contenttype ?? [],
                ['citygovOnlineService'],
            );
        }
    }


    private function enrichLeikaNumber($leikaKeys, &$doc): void
    {
        if (!empty($leikaKeys)) {
            $doc->setMetaString('leikanumber', $leikaKeys);
        }
    }

    /**
     * @template E of IndexSchema2xDocument
     * @param E $doc
     * @return E
     */
    private function enrichContent(
        Resource $resource,
        IndexDocument $doc,
    ): IndexDocument {

        $contentCollector = new ContentCollector([
            new RichtTextMatcher(),
        ]);

        $content = $contentCollector->collect(
            $resource->data->getArray(
                'metadata.citygovProduct.content',
            ),
            $resource,
        );
        $cleanContent = preg_replace(
            '/\s+/',
            ' ',
            $content,
        );

        $doc->content = trim(
            ($doc->content ?? '') . ' ' . $cleanContent,
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
        IndexDocument &$doc,
    ): IndexDocument {

        /** @var Responsibility[] $responsibilityList */
        $responsibilityList = $resource->data->getAssociativeArray(
            'metadata.citygovProduct.responsibilityList.items',
        );
        try {
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
                        $resource->lang,
                    ),
                );
                $doc = $this->organisationEnricher->enrichOrganisationPath(
                    $primaryOrganisationResource,
                    $doc,
                );
                break;
            }
        } catch (Exception $e) {
            throw new DocumentEnrichingException(
                $resource->toLocation(),
                'Unable to enrich organisation_path for product',
                0,
                $e,
            );
        }

        return $doc;
    }
}
