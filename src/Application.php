<?php

declare(strict_types=1);

namespace Palmyr\App;

use Palmyr\Console\Application as BaseApplication;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Application extends BaseApplication
{
    protected function __construct()
    {
        parent::__construct("aws-common-utils", "1.3.3");
    }

    protected function loadExtras(ContainerBuilder $containerBuilder): void
    {
        parent::loadExtras($containerBuilder);

        $fileLocator = new FileLocator(__DIR__ . "/../config");

        $loader = new YamlFileLoader($containerBuilder, $fileLocator);

        $loader->load('services.yaml');
    }
}
