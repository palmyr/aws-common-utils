<?php declare(strict_types=1);

namespace Palmyr\App\Command;

use Palmyr\App\Holder\SdkHolderInterface;
use Aws\Sdk;
use Palmyr\App\Service\AwsIniFileServiceInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListInstancesCommand extends AbstractAWSCommand
{

    public function __construct(SdkHolderInterface $sdkHolder, AwsIniFileServiceInterface $iniFileService)
    {
        parent::__construct($sdkHolder, $iniFileService, 'ec2:list_instances');
    }

    protected function runCommand(InputInterface $input, SymfonyStyle $io): int
    {
        $ec2Client = $this->getSdk()->createEc2();

        $result = $ec2Client->describeInstances();

        $reservations = $result->get('Reservations');

        $headers = [
            'InstanceId',
            'InstanceType',
            "PublicIpAddress",
            "State"
        ];
        $rows = [];

        foreach ( $reservations as $reservation ) {
            if ( isset($reservation['Instances'][0]) && ($instance = $reservation['Instances'][0]) ) {
                $rows[] = [
                    $instance['InstanceId'],
                    $instance['InstanceType'],
                    $instance["PublicIpAddress"],
                    $instance["State"]["Name"]
                ];
            }
        }

        $io->table($headers, $rows);

        return self::SUCCESS;
    }
}
