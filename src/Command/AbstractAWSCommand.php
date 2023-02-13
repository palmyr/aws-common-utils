<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Palmyr\SymfonyAws\Command\AbstractAwsCommand AS BaseAbstractAwsCommand;
use Palmyr\App\Holder\SdkHolderInterface;
use Aws\Sdk;
use Psr\Container\ContainerInterface;

abstract class AbstractAWSCommand extends BaseAbstractAwsCommand
{
    private SdkHolderInterface $sdkHolder;

    public function __construct(
        ContainerInterface $container,
        SdkHolderInterface $sdkHolder,
        string $name
    )
    {
        $this->sdkHolder = $sdkHolder;
        parent::__construct($container, $name);
    }

    protected function getSdk(): Sdk
    {
        return $this->sdkHolder->getSdk();
    }
}
