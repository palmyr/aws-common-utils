<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Palmyr\App\Holder\SdkHolderInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GetCallerIdentityCommand extends AbstractAWSCommand
{

    public function __construct(SdkHolderInterface $sdkHolder)
    {
        parent::__construct($sdkHolder, "sts:get_caller_identity");
    }

    protected function configure()
    {
        parent::configure();
        $this->setDescription("Get the current users account details");
    }
    protected function runCommand(InputInterface $input, SymfonyStyle $io): int
    {
        $stsClient = $this->getSdk()->createSts();

        $result = $stsClient->getCallerIdentity();

        $headers = [
            "Account",
            "Arn",
            "UserId",
        ];

        $row = [
            $result->get("Account"),
            $result->get("Arn"),
            $result->get("UserId")
        ];

        $io->table($headers, [$row]);

        return self::SUCCESS;
    }
}
