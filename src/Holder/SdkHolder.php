<?php

declare(strict_types=1);

namespace Palmyr\App\Holder;

use Aws\AwsClientInterface;
use Aws\Sdk;
use Palmyr\App\Enum\ErrorMessages;
use Palmyr\App\Exception\SdkBuildException;
use Palmyr\App\Factory\SdkFactoryInterface;
use Palmyr\App\Service\AwsIniFileServiceInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

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
        if (isset($this->sdk)) {
            return $this->sdk;
        }

        throw new RuntimeException('The sdk has not been set yet');
    }

    public function buildSdk(string $profile, string $region = null): SdkHolderInterface
    {
        $data = $this->iniFileService->parseAwsIni();

        if (!$profileData = $data->getProfile($profile)) {
            throw new SdkBuildException(ErrorMessages::PROFILE_NOT_FOUND);
        }

        if (is_null($region)) {
            $region = $profileData->get("region");
        }

        $this->sdk = $this->sdkFactory->build([
            "profile" => $profile,
            "region" => $region,
        ]);

        return $this;
    }
}
