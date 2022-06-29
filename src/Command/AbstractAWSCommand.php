<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Palmyr\App\Holder\SdkHolderInterface;
use Aws\Sdk;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractAWSCommand extends AbstractAWSConfigurationCommand
{

    private SdkHolderInterface $sdkHolder;

    public function __construct(
        SdkHolderInterface $sdkHolder,
        string $name = null
    ) {
        $this->sdkHolder = $sdkHolder;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->sdkHolder->buildSdk($input);
        $io = $this->prepareIO($input, $output);

        return $this->runCommand($input, $io);
    }

    abstract protected function runCommand(InputInterface $input, SymfonyStyle $io): int;

    private function prepareIO(InputInterface $input, OutputInterface $output): SymfonyStyle
    {
        return new SymfonyStyle($input, $output);
    }

    protected function getSdk(): Sdk
    {
        return $this->sdkHolder->getSdk();
    }
}
