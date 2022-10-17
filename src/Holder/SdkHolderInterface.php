<?php

declare(strict_types=1);

namespace Palmyr\App\Holder;

use Aws\AwsClientInterface;
use Aws\Sdk;
use Palmyr\App\Exception\SdkBuildException;

interface SdkHolderInterface
{
    /**
     * @param string $profile
     * @param string|null $region
     * @return SdkHolderInterface
     * @throws SdkBuildException
     */
    public function buildSdk(string $profile, string $region = null): SdkHolderInterface;

    public function getSdk(): Sdk;
}
