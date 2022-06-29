<?php

declare(strict_types=1);

namespace Palmyr\App\Service;

interface AwsIniFileServiceInterface
{
    public const AWS_INI_FILENAME = 'credentials';

    public const AWS_INI_FILENAME_MFA = self::AWS_INI_FILENAME . '_mfa';

    public function parseAwsIni(string $filename = self::AWS_INI_FILENAME): array;

    public function writeAwsIni(array $data, string $filename = self::AWS_INI_FILENAME_MFA): void;
}
