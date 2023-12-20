<?php
declare(strict_types=1);

namespace Palmyr\App\Command;

use Palmyr\App\Holder\SdkHolderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleLoginUrlCommand extends AbstractAWSCommand
{

    public function __construct(
        ContainerInterface $container,
        SdkHolderInterface $sdkHolder
    )
    {
        parent::__construct($container, $sdkHolder, "iam:console_login_url");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $client = $this->getSdk()->createSts();

        $result = $client->getCallerIdentity();

        $accountId = (string)$result->get("Account");

        $io->success(sprintf("Your console login url https://%s.signin.aws.amazon.com/console/", $accountId));

        return self::SUCCESS;
    }
}