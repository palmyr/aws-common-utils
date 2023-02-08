<?php

declare(strict_types=1);

namespace Palmyr\App\Holder;

use Aws\AwsClientInterface;
use Aws\Sdk;
use Palmyr\App\Enum\ErrorMessages;
use Palmyr\App\Exception\SdkBuildException;
use Palmyr\App\Service\AwsIniFileServiceInterface;
use Palmyr\SymfonyAws\Factory\SdkFactoryInterface;
use Symfony\Component\Console\Exception\RuntimeException;

class SdkHolder implements SdkHolderInterface
{
    protected Sdk $sdk;

    protected AwsIniFileServiceInterface $iniFileService;

    protected SdkFactoryInterface $sdkFactory;

    public function __construct(
        AwsIniFileServiceInterface $iniFileService,
        SdkFactoryInterface $sdkFactory,
    ) {
        $this->iniFileService = $iniFileService;
        $this->sdkFactory = $sdkFactory;
    }

    public function getSdk(): Sdk
    {
        if (!isset($this->sdk)) {

            $this->sdk = $this->sdkFactory->build([]);
        }

        return $this->sdk;
    }
}
