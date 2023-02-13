<?php

declare(strict_types=1);

namespace Palmyr\App\Command;


use Palmyr\App\Holder\SdkHolderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ListInstancesCommand extends AbstractAwsCommand
{

    protected const INSTANCE_KEYS = [
        "[InstanceId]",
        "[InstanceType]",
        "[PublicIpAddress]",
        "[PrivateIpAddress]",
        "[State][Name]",
    ];

    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        ContainerInterface $container,
        SdkHolderInterface $sdkHolder,
        PropertyAccessorInterface $propertyAccessor
    )
    {
        $this->propertyAccessor = $propertyAccessor;
        parent::__construct($container, $sdkHolder, "ec2:list_instances");
    }

    protected function configure()
    {
        parent::configure();
        $this->setDescription("List all instances");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $ec2Client = $this->getSdk()->createEc2();

        $result = $ec2Client->describeInstances();

        $reservations = $result->get("Reservations");

        $headers = [
            "InstanceName",
            "InstanceId",
            "InstanceType",
            "PublicIpAddress",
            "PrivateIpAddress",
            "State"
        ];
        $rows = [];

        foreach ($reservations as $reservation) {
            if (isset($reservation["Instances"][0]) && ($instance = $reservation["Instances"][0])) {
                $name = "";
                foreach ($instance["Tags"] as $tag) {
                    if ($tag["Key"] === "Name") {
                        $name = $tag["Value"];
                    }
                }
                $row = [
                    $name
                ];
                foreach (self::INSTANCE_KEYS as $key) {
                    $row[] = $this->propertyAccessor->getValue($instance, $key);
                }
                $rows[] = $row;
            }
        }

        $io->table($headers, $rows);

        return self::SUCCESS;
    }
}
