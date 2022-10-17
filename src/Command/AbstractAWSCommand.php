<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Palmyr\App\Exception\SdkBuildException;
use Palmyr\App\Holder\SdkHolderInterface;
use Aws\Sdk;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractAWSCommand extends AbstractAWSConfigurationCommand
{
    protected SymfonyStyle $io;

    private SdkHolderInterface $sdkHolder;

    public function __construct(
        SdkHolderInterface $sdkHolder,
        string $name = null
    ) {
        $this->sdkHolder = $sdkHolder;
        parent::__construct($name);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->io = $this->prepareIO($input, $output);
        $this->setCode([$this, "buildCommand"]);
    }

    public function buildCommand(InputInterface $input, OutputInterface $output): int
    {
        $profile = (string)$input->getOption("profile");
        $region = $input->getOption("region") ?: null;

        try {
            $this->sdkHolder->buildSdk($profile, $region);
        } catch (SdkBuildException $e) {
            $this->io->error("The profile [{$profile}] could not be found");
            return self::FAILURE;
        }

        return $this->runCommand($input, $this->io);
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
