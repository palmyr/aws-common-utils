<?php

declare(strict_types=1);

namespace Palmyr\App\Service;

interface InstanceIpServiceInterface
{
    public const FIRST_INSTANCE = 0;

    public function getByInstanceId(string $instanceId, bool $public = true): string;

    public function getByAutoscalingGroupName(string $autoScalingGroupName, bool $public = true, int $key = self::FIRST_INSTANCE): string;
}
