<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Palmyr\App\Holder\SdkHolderInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListInstancesCommand extends AbstractAWSCommand
{
    public function __construct(SdkHolderInterface $sdkHolder)
    {
        parent::__construct($sdkHolder, "ec2:list_instances");
    }

    protected function configure()
    {
        parent::configure();
        $this->setDescription("List all instances");
    }

    protected function runCommand(InputInterface $input, SymfonyStyle $io): int
    {
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
                $rows[] = [
                    $name,
                    $instance["InstanceId"] ?: null,
                    $instance["InstanceType"] ?: null,
                    $instance["PublicIpAddress"] ?: null,
                    $instance["PrivateIpAddress"] ?: null,
                    $instance["State"]["Name"] ?: null
                ];
            }
        }

        $io->table($headers, $rows);

        return self::SUCCESS;
    }
}
