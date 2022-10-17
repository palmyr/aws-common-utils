<?php declare(strict_types=1);

namespace Palmyr\App\Manager;

interface S3ManagerInterface
{

    public function bucketExists(string $bucketName): bool;

}