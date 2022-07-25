<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Aws\Ec2\Ec2Client;
use Aws\Sdk;
use Palmyr\App\Holder\SdkHolderInterface;
use Palmyr\App\Service\InstanceIpServiceInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

class InstanceIPCommand extends AbstractInstanceIPCommand
{
    public function __construct(
        SdkHolderInterface $sdkHolder,
        InstanceIpServiceInterface $instanceIpService
    ) {
        parent::__construct($sdkHolder, $instanceIpService, "ec2:instance_ip");
    }


    protected function configure()
    {
        parent::configure();
        $this->setDescription("get the instances ip address by its id");
        $this->addOption("instance_id", "i", InputOption::VALUE_OPTIONAL, "The instance id");
    }

    protected function getInstanceIp(InputInterface $input, SymfonyStyle $io): string
    {
        $public = $this->getPrivateOption($input);
        return $this->instanceIpService->getByInstanceId($input->getOption("instance_id"), $public);
    }
}
