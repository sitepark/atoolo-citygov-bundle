<?php

declare(strict_types=1);

namespace Atoolo\CityGov\Service\GraphQL\Factory;

use Atoolo\CityGov\Service\GraphQL\Types\OnlineService;
use Atoolo\CityGov\Service\GraphQL\Types\OnlineServiceFeature;
use Atoolo\GraphQL\Search\Factory\TeaserFeatureFactory;
use Atoolo\GraphQL\Search\Types\Link;
use Atoolo\GraphQL\Search\Types\TeaserFeature;
use Atoolo\Resource\Resource;

class OnlineServiceFeatureFactory implements TeaserFeatureFactory
{
    /**
     * @return TeaserFeature[]
     */
    public function create(
        Resource $resource,
    ): array {
        $onlineServiceFeatures = [];
        if ($resource->data->has('metadata.citygovProduct.onlineServices')) {
            $onlineServices = [];

            /** @var array{url: string} $onlineServiceRaw */
            foreach (
                $resource->data->getArray('metadata.citygovProduct.onlineServices.serviceList.items') as $onlineServiceRaw
            ) {
                $onlineServices[] = new OnlineService(
                    new Link($onlineServiceRaw['url']),
                );
            }
            $onlineServiceFeatures[] = new OnlineServiceFeature(
                null,
                $onlineServices,
            );
        }
        return $onlineServiceFeatures;
    }
}
