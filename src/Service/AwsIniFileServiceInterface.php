<?php

declare(strict_types=1);

namespace Palmyr\App\Service;

use Palmyr\App\Model\AwsIniModelInterface;

interface AwsIniFileServiceInterface
{
    public const AWS_INI_FILENAME = 'credentials';

    public const AWS_INI_FILENAME_MFA = self::AWS_INI_FILENAME . '_mfa';

    public function parseAwsIni(string $filename): AwsIniModelInterface;

    public function writeAwsIni(array $data, string $filename): void;
}
