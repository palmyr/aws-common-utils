<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GetCallerIdentityCommand extends AbstractAWSCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName("sts:get_caller_identity");
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
