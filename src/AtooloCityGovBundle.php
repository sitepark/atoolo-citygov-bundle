<?php

declare(strict_types=1);

namespace Atoolo\CityGov;

use Symfony\Component\Config\Loader\GlobFileLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @codeCoverageIgnore
 */
class AtooloCityGovBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->setParameter(
            'atoolo_citygov.src_dir',
            __DIR__,
        );
        $configDir = __DIR__ . '/../config';
        $container->setParameter(
            'atoolo_citygov.config_dir',
            $configDir,
        );

        $loader = new GlobFileLoader(new FileLocator($configDir));
        $loader->setResolver(
            new LoaderResolver(
                [
                    new YamlFileLoader($container, new FileLocator($configDir)),
                ],
            ),
        );
        $loader->load('graphql.yaml');
        $loader->load('services.yaml');
    }
}
