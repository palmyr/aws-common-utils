<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Palmyr\App\Holder\SdkHolderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Palmyr\App\Service\InstanceIpServiceInterface;

class AutoScalingGroupIpCommand extends AbstractInstanceIPCommand
{
    public function __construct(
        ContainerInterface $container,
        SdkHolderInterface $sdkHolder,
        InstanceIpServiceInterface $instanceIpService
    ) {
        parent::__construct($container, $sdkHolder, $instanceIpService, 'ec2:autoscaling_group_ip');
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setDescription("Get a instances ip from an autoscaling group.")
            ->addOption('key', 'k', InputOption::VALUE_OPTIONAL, 'Which instance key to use', 0)
            ->addOption('auto_scaling_group_name', 'g', InputOption::VALUE_OPTIONAL, 'The autoscaling group name to connect to');
    }

    protected function getInstanceIp(InputInterface $input, SymfonyStyle $io): string
    {
        $key = (int)$input->getOption('key');
        $autoScalingGroupName = (string)$input->getOption('auto_scaling_group_name');
        $private = $this->getPrivateOption($input);

        return $this->instanceIpService->getByAutoscalingGroupName($autoScalingGroupName, $private, $key);
    }
}
