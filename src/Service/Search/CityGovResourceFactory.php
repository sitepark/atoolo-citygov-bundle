<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\Search;

use Atoolo\CityGov\ChannelAttributes;
use Atoolo\Resource\DataBag;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Resource\ResourceLocation;
use Atoolo\Search\Service\Search\ResourceFactory;
use LogicException;
use Solarium\QueryType\Select\Result\Document;

class CityGovResourceFactory implements ResourceFactory
{
    public function __construct(
        private readonly ResourceLoader $resourceLoader,
        private readonly ChannelAttributes $channelAttributes,
    ) {}

    public function accept(Document $document, ResourceLanguage $lang): bool
    {
        $location = $this->getUrl($document);
        if ($location === null) {
            return false;
        }
        $location = explode('?', $location)[0];
        return str_ends_with($location, '.php');
    }

    public function create(Document $document, ResourceLanguage $lang): Resource
    {
        $url = $this->getUrl($document);
        if ($url === null) {
            throw new LogicException('document should contain an url');
        }
        $url = explode('?', $url)[0];
        $location = ResourceLocation::of($url, $lang);
        $resource = $this->resourceLoader->load($location);

        if ($this->channelAttributes->addAlternativeDocuments) {
            $resource = $this->applyAlternativeTitle($resource, $document);
        }

        return $resource;
    }

    private function applyAlternativeTitle(
        Resource $resource,
        Document $document,
    ): Resource {
        $docUrl = $this->getUrl($document) ?? '';
        $queryString = parse_url($docUrl, PHP_URL_QUERY);
        parse_str(is_string($queryString) ? $queryString : '', $queryParams);

        $atParam = $queryParams['cg_at_id'] ?? null;
        if ($atParam === null) {
            return $resource;
        }

        $index = (int) $atParam;
        $alternativeNameList = $resource->data->getArray(
            'metadata.citygovProduct.alternativeNameList',
        );

        if (!isset($alternativeNameList[$index])) {
            return $resource;
        }

        $title = $alternativeNameList[$index];
        if (!is_string($title)) {
            return $resource;
        }

        $data = array_replace_recursive(
            $resource->data->get(),
            [
                'metadata' => ['headline' => $title],
                'base' => ['teaser' => ['headline' => $title]],
                'url' => $docUrl,
            ],
        );

        return new Resource(
            location: $resource->location,
            url: $resource->url,
            id: $resource->id,
            name: $resource->name,
            objectType: $resource->objectType,
            lang: $resource->lang,
            data: new DataBag($data),
        );
    }

    private function getUrl(Document $document): ?string
    {
        return $document->getFields()['url'] ?? null;
    }
}
