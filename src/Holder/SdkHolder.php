<?php

declare(strict_types=1);

namespace Palmyr\App\Holder;

use Aws\Sdk;

class SdkHolder implements SdkHolderInterface
{
    protected Sdk $sdk;

    public function getSdk(): Sdk
    {
        if (isset($this->sdk)) {
            return $this->sdk;
        }

        throw new \RuntimeException('The sdk has not been set yet');
    }

    public function setSdk(Sdk $sdk): SdkHolderInterface
    {
        $this->sdk = $sdk;

        return $this;
    }
}
