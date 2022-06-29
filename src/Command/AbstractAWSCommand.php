<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Palmyr\App\Holder\SdkHolderInterface;
use Aws\Sdk;
use Palmyr\App\Service\AwsIniFileServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractAWSCommand extends Command
{

    protected SdkHolderInterface $sdkHolder;

    protected AwsIniFileServiceInterface $iniFileService;

    public function __construct(
        SdkHolderInterface $sdkHolder,
        AwsIniFileServiceInterface $iniFileService,
        string $name = null
    ) {
        $this->sdkHolder = $sdkHolder;
        $this->iniFileService = $iniFileService;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->prepareSDK($input);
        $io = $this->prepareIO($input, $output);

        return $this->runCommand($input, $io);
    }

    abstract protected function runCommand(InputInterface $input, SymfonyStyle $io): int;

    protected function configure()
    {
        parent::configure();
        $this->addOption("profile", "p", InputOption::VALUE_REQUIRED, "The profile to use");
        $this->addOption("region", "r", InputOption::VALUE_OPTIONAL, "The region to use");
    }

    private function prepareIO(InputInterface $input, OutputInterface $output): SymfonyStyle
    {
        return new SymfonyStyle($input, $output);
    }

    protected function prepareSDK(InputInterface $input): void
    {

        $profile = (string)$input->getOption("profile");

        $data = $this->iniFileService->parseAwsIni();

        if ( !array_key_exists($profile, $data) ) {
            throw new \RuntimeException("Failed to find profile");
        }
        $profileData = $data[$profile];

        $region = $profileData["region"];

        if ($input->getOption("region")) {
            $region = $input->getOption("region");
        }

        $sdk = new Sdk([
            "version" => "latest",
            "profile" => $profile,
            "region" => $region,
        ]);

        $this->sdkHolder->setSdk($sdk);
    }

    protected function getSdk(): Sdk
    {
        return $this->sdkHolder->getSdk();
    }
}
