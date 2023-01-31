<?php declare(strict_types=1);

namespace Palmyr\App\Manager;

use Palmyr\App\Model\S3BucketSummaryModelInterface;

interface S3ManagerInterface
{

    public function bucketExists(string $bucketName): bool;

    public function getBucketSummary(string $bucketName): S3BucketSummaryModelInterface;

    public function purgeBucket(string $bucketName, bool $versioned = false, callable $indicator = null): S3ManagerInterface;

}