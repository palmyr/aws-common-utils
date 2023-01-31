<?php declare(strict_types=1);

namespace Palmyr\App\Command;

use Palmyr\App\Holder\SdkHolderInterface;
use Palmyr\App\Manager\S3ManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PurgeBucketCommand extends AbstractAWSCommand
{

    protected const ZERO_ITEMS = 0;

    protected S3ManagerInterface $s3Manager;

    public function __construct(
        SdkHolderInterface $sdkHolder,
        S3ManagerInterface $s3Manager
    )
    {
        $this->s3Manager = $s3Manager;
        parent::__construct($sdkHolder, "s3:purge_bucket");
    }

    protected function configure()
    {
        parent::configure();
        $this->setDescription("Purge a bucket of all content and delete it.");
        $this->addArgument("bucket_name", InputArgument::REQUIRED, "The bucket name");
    }

    protected function runCommand(InputInterface $input, SymfonyStyle $io): int
    {

        $bucketName = (string)$input->getArgument("bucket_name");

        if ( !$this->s3Manager->bucketExists($bucketName) ) {
            $this->io->warning("The bucket does not exist");
            return self::FAILURE;
        }

        $summary = $this->s3Manager->getBucketSummary($bucketName);

        if ( $summary->getCount() > self::ZERO_ITEMS ) {
            $io->warning("The bucket is not empty [Count: {$summary->getCount()} ] [Size: {$summary->getSize()} ]");
            if (!$io->confirm("Purge", false)) {
                return self::SUCCESS;
            }
        }

        $this->s3Manager->purgeBucket($bucketName, true);



        return self::SUCCESS;
    }


}