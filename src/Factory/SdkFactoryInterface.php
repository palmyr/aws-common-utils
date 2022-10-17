<?php

declare(strict_types=1);

namespace Palmyr\App\Factory;

use Aws\Sdk;

interface SdkFactoryInterface
{
    public function build(array $options): Sdk;
}
