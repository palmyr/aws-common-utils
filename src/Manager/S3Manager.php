<?php declare(strict_types=1);

namespace Palmyr\App\Manager;

use Aws\S3\S3Client;
use Palmyr\App\Holder\SdkHolderInterface;

class S3Manager implements S3ManagerInterface
{

    protected S3Client $client;

    protected SdkHolderInterface $sdkHolder;

    public function __construct(
        SdkHolderInterface $sdkHolder
    )
    {
        $this->sdkHolder = $sdkHolder;
    }


    public function bucketExists(string $bucketName): bool
    {
        return $this->getClient()->doesBucketExist($bucketName);
    }

    protected function getClient(): S3Client
    {
        if (!isset($this->client) ) {
            $this->client = $this->sdkHolder->getSdk()->createS3();
        }

        return $this->client;
    }
}