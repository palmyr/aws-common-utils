<?php

declare(strict_types=1);

namespace Palmyr\App\Holder;

use Aws\Sdk;
use Palmyr\App\Exception\SdkBuildException;
use Symfony\Component\Console\Input\InputInterface;

interface SdkHolderInterface
{
    /**
     * @param InputInterface $input
     * @return SdkHolderInterface
     * @throws SdkBuildException
     */
    public function buildSdk(InputInterface $input): SdkHolderInterface;

    public function getSdk(): Sdk;
}
