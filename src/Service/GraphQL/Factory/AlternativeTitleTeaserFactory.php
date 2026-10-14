<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\GraphQL\Factory;

use Atoolo\GraphQL\Search\Factory\LinkFactory;
use Atoolo\GraphQL\Search\Factory\TeaserFactory;
use Atoolo\GraphQL\Search\Types\ArticleTeaser;
use Atoolo\GraphQL\Search\Types\Teaser;
use Atoolo\Resource\Resource;

class AlternativeTitleTeaserFactory implements TeaserFactory
{
    public function __construct(
        private readonly LinkFactory $linkFactory,
    ) {}

    public function create(Resource $resource): Teaser
    {
        $url = $resource->data->getString('url');
        $linkResource = $url !== '' ? new Resource(
            location: $url,
            id: $resource->id,
            name: $resource->name,
            objectType: $resource->objectType,
            lang: $resource->lang,
            data: $resource->data,
        ) : $resource;

        $teaserLink = $this->linkFactory->create($linkResource);
        $teaserHeadline = $resource->data->getString('base.teaser.headline', $resource->name, );
        $teaserText = $resource->data->getString('base.teaser.text');

        return new ArticleTeaser(
            $teaserLink,
            $teaserHeadline,
            $teaserText === '' ? null : $teaserText,
            $resource,
        );
    }
}
