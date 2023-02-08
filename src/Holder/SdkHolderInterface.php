<?php

declare(strict_types=1);

namespace Palmyr\App\Holder;

use Aws\AwsClientInterface;
use Aws\Sdk;
use Palmyr\App\Exception\SdkBuildException;

interface SdkHolderInterface
{

    public function getSdk(): Sdk;
}
