<?php

declare(strict_types=1);

namespace Palmyr\App\Service;

interface AwsIniFileServiceInterface
{
    public const MFA_SUFFIX = '_mfa';

    public const MFA_SUFFIX_LENGTH = 4;

    public function parseAwsIni(string $filename = 'credentials'): array;

    public function writeAwsIni(array $data, string $filename = 'credentials'): void;
}
