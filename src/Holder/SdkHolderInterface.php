<?php

declare(strict_types=1);

namespace Palmyr\App\Holder;

use Aws\Sdk;

interface SdkHolderInterface
{
    public function setSdk(Sdk $sdk): SdkHolderInterface;

    public function getSdk(): Sdk;
}
