<?php

declare(strict_types=1);

namespace Palmyr\App;

use Palmyr\Console\Application as BaseApplication;
use Palmyr\SymfonyAws\DependencyInjection\SymfonyAwsExtension;
use Palmyr\SymfonyCommonUtils\DependencyInjection\SymfonyCommonUtilsExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Application extends BaseApplication
{
    protected function __construct()
    {
        parent::__construct("aws-common-utils", "1.3.6");
    }

    protected function getExtensions(): array
    {
        return array_merge(parent::getExtensions(),[
            new SymfonyAwsExtension(),
            new SymfonyCommonUtilsExtension(),
        ]);
    }
}
