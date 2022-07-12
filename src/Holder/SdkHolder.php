<?php

declare(strict_types=1);

namespace Palmyr\App\Holder;

use Aws\Sdk;
use Palmyr\App\Exception\SdkBuildException;
use Palmyr\App\Service\AwsIniFileServiceInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class SdkHolder implements SdkHolderInterface
{
    protected Sdk $sdk;

    protected AwsIniFileServiceInterface $iniFileService;

    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        AwsIniFileServiceInterface $iniFileService,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->iniFileService = $iniFileService;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function getSdk(): Sdk
    {
        if (isset($this->sdk)) {
            return $this->sdk;
        }

        throw new \RuntimeException('The sdk has not been set yet');
    }

    public function buildSdk(InputInterface $input): SdkHolderInterface
    {
        $profile = (string)$input->getOption("profile");

        $data = $this->iniFileService->parseAwsIni();

        if (!$profileData = $this->propertyAccessor->getValue($data, "[{$profile}]")) {
            throw new SdkBuildException("Could not find the requested profile.");
        }

        $region = $this->propertyAccessor->getValue($profileData, "[region]");
        if ($input->getOption("region")) {
            $region = $input->getOption("region");
        }

        $this->sdk = new Sdk([
            "version" => "latest",
            "profile" => $profile,
            "region" => $region,
        ]);

        return $this;
    }
}
