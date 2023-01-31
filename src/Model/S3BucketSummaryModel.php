<?php declare(strict_types=1);

namespace Palmyr\App\Model;

class S3BucketSummaryModel implements S3BucketSummaryModelInterface
{

    protected int $count;

    protected int $size;

    public function __construct(
        int $count,
        int $size
    )
    {
        $this->count = $count;
        $this->size = $size;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }




}