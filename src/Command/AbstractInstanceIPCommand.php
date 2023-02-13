<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Aws\Sdk;
use Palmyr\App\Holder\SdkHolderInterface;
use Palmyr\App\Service\InstanceIpServiceInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractInstanceIPCommand extends AbstractAWSCommand
{
    protected InstanceIpServiceInterface $instanceIpService;

    public function __construct(
        ContainerInterface $container,
        SdkHolderInterface $sdkHolder,
        InstanceIpServiceInterface $instanceIpService,
        string $name = null
    ) {
        parent::__construct($container, $sdkHolder, $name);
        $this->instanceIpService = $instanceIpService;
    }

    protected function configure()
    {
        parent::configure();
        $this->addOption("private", "a", InputOption::VALUE_OPTIONAL, "Get the instances private ip.", false);
    }

    protected function runCommand(InputInterface $input, SymfonyStyle $io): int
    {
        $io->write($this->getInstanceIp($input, $io));

        return self::SUCCESS;
    }

    abstract protected function getInstanceIp(InputInterface $input, SymfonyStyle $io): string;

    protected function getPrivateOption(InputInterface $input): bool
    {
        return $input->getOption("private") === false;
    }
}
