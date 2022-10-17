<?php

declare(strict_types=1);

namespace Palmyr\App\Service;

use Aws\Credentials\CredentialProvider;
use Palmyr\App\Model\AwsIniModel;
use Palmyr\App\Model\AwsIniModelInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class AwsIniFileService implements AwsIniFileServiceInterface
{
    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->propertyAccessor = $propertyAccessor;
    }

    public function parseAwsIni(string $filename = self::AWS_INI_FILENAME): AwsIniModelInterface
    {
        $data =  \Aws\parse_ini_file($this->getFileName($filename), true, INI_SCANNER_RAW);

        return new AwsIniModel($data);
    }

    public function writeAwsIni(array $data, string $filename = self::AWS_INI_FILENAME): void
    {
        $file = new  \SplFileObject($this->getFileName($filename), 'w');

        foreach ($data as $profile => $profileData) {
            $file->fwrite('['.$profile.']' . PHP_EOL);
            foreach ($profileData as $key => $value) {
                $file->fwrite($key.'='.$value . PHP_EOL);
            }

            $file->fwrite(PHP_EOL);
        }

        $file->fflush();
    }

    protected function getFileName(string $filename): string
    {
        return getenv(CredentialProvider::ENV_SHARED_CREDENTIALS_FILE) ?: (self::getHomeDir() . "/.aws/" . $filename);
    }

    protected function getHomeDir(): ?string
    {
        // On Linux/Unix-like systems, use the HOME environment variable
        if ($homeDir = getenv('HOME')) {
            return $homeDir;
        }

        // Get the HOMEDRIVE and HOMEPATH values for Windows hosts
        $homeDrive = getenv('HOMEDRIVE');
        $homePath = getenv('HOMEPATH');

        return ($homeDrive && $homePath) ? $homeDrive . $homePath : null;
    }
}
