<?php

declare(strict_types=1);

namespace Palmyr\App\Factory;

use Aws\Sdk;

class SdkFactory implements SdkFactoryInterface
{
    public function build(array $options): Sdk
    {
        $options["version"] = "latest";

        return new Sdk($options);
    }
}
