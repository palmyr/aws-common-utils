<?php declare(strict_types=1);

namespace Palmyr\App\Manager;

use Aws\S3\S3Client;
use Palmyr\App\Holder\SdkHolderInterface;
use Palmyr\App\Model\S3BucketSummaryModel;
use Palmyr\App\Model\S3BucketSummaryModelInterface;
use Palmyr\CommonUtils\Iterator\IteratorArrayIterator;

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

    public function getBucketSummary(string $bucketName): S3BucketSummaryModelInterface
    {

        $iterator = $this->getBucketIterator($bucketName);
        $count = 0;
        $size = 0;
        foreach ($iterator as $object) {
            $count++;
            $size += isset($object["Size"]) ? (int)$object["Size"] : 0;
        }

        return new S3BucketSummaryModel(
            count: $count,
            size: $size
        );
    }

    public function purgeBucket(string $bucketName, bool $versioned = false, callable $indicator = null): S3ManagerInterface
    {
        if ( $versioned ) {
            $this->purgeVersionedBucket($bucketName, $indicator);
        } else {
            $this->purgeNonVersionedBucket($bucketName, $indicator);
        }

        $this->deleteBucket($bucketName);

        return $this;
    }

    public function deleteObject(string $bucketName, string $key, ?string $version = null): S3ManagerInterface
    {

        $params = [
            "Bucket" => $bucketName,
            "Key" => $key,
        ];

        if ( !empty($version) ) {
            $params["VersionId"] = $version;
        }

        $result = $this->getClient()->deleteObject($params);

        return $this;
    }

    public function deleteBucket(string $bucketName): S3ManagerInterface
    {
        $this->getClient()->deleteBucket([
            "Bucket" => $bucketName,
        ]);

        return $this;
    }

    protected function getBucketIterator(string $bucketName): \Iterator
    {
        $args = [
            "Bucket" => $bucketName,
            "Recursive" => true,
        ];
        return new IteratorArrayIterator([
            $this->getDeleteMarkerIterator($args),
            $this->getClient()->getIterator('ListObjectVersions', $args),
        ]);
    }

    protected function getDeleteMarkerIterator(array $args): iterable
    {
        return $this->getClient()->getPaginator("ListObjectVersions", $args)->search("DeleteMarkers");
    }

    protected function purgeNonVersionedBucket(string $bucketName, callable $indicator = null): S3ManagerInterface
    {

    }

    protected function purgeVersionedBucket(string $bucketName, callable $indicator = null): S3ManagerInterface
    {
        $iterator = $this->getBucketIterator($bucketName);

        foreach ($iterator as $object) {
            $key = (string)$object["Key"];
            $version = (string)$object["VersionId"];
            $this->deleteObject($bucketName, $key, $version);
        }

        return $this;
    }

    protected function getClient(): S3Client
    {
        if (!isset($this->client) ) {
            $this->client = $this->sdkHolder->getSdk()->createS3();
        }

        return $this->client;
    }
}