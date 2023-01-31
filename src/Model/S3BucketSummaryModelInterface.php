<?php declare(strict_types=1);

namespace Palmyr\App\Model;

interface S3BucketSummaryModelInterface
{

    public function getCount(): int;

    public function getSize(): int;


}