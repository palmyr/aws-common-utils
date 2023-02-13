<?php

declare(strict_types=1);

namespace Palmyr\App\Holder;

use Aws\Sdk;
use Palmyr\SymfonyAws\Service\AwsIniFileServiceInterface;
use Palmyr\SymfonyAws\Factory\SdkFactoryInterface;

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
            $this->sdk = $this->sdkFactory->buildFromProfile();
        }

        return $this->sdk;
    }
}
