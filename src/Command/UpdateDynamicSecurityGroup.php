<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Aws\Ec2\Ec2Client;
use Palmyr\App\Holder\SdkHolderInterface;
use Palmyr\CommonUtils\IpInfo\Service\IpInfoServiceInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateDynamicSecurityGroup extends AbstractAWSCommand
{
    protected const MIN_PORTS = 1;

    protected IpInfoServiceInterface $ipInfoService;

    public function __construct(
        ContainerInterface $container,
        SdkHolderInterface $sdkHolder,
        IpInfoServiceInterface $ipInfoService
    ) {
        parent::__construct($container, $sdkHolder, "security-group:update_dynamic");
        $this->ipInfoService = $ipInfoService;
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription("Use this command to update a security groups ingress rules");
        $this->addArgument("group_id", InputArgument::REQUIRED, "The security group id");
        $this->addArgument("description", InputArgument::REQUIRED, "The description used to identify the existing rule");
        $this->addOption("port", null, InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, "The port to allow access");
        $this->addOption("ip", null, InputOption::VALUE_REQUIRED, "The ip to allow access");
    }

    protected function runCommand(InputInterface $input, SymfonyStyle $io): int
    {
        $client = $this->getSdk()->createEc2();

        $ports = (array)$input->getOption("port");

        if (!$ip = (string)$input->getOption("ip")) {
            $ip = $this->ipInfoService->getIp();
        }

        $cidr = $ip . "/32";

        if (count($ports) < self::MIN_PORTS) {
            $io->error("At least one port number is required");
            return self::INVALID;
        }

        $groupId = (string)$input->getArgument("group_id");
        $description = (string)$input->getArgument("description");

        $existingIpPermissions = $this->getSecurityGroupRules($client, $groupId);
        foreach ($ports as $port) {
            $port = (int)$port;
            $io->comment("Checking port {$port}");
            $existingIpPermission = $this->getIpPermissionsByPort($existingIpPermissions, $port);
            if ($this->hasRule($existingIpPermission, $cidr)) {
                $io->success("Rule already exists");
            } else {
                $cidrCollection = $this->getExistingCidrCollection($existingIpPermission, $port, $description);
                if (count($existingIpPermission) > 0) {
                    $this->revokeRule($client, $groupId, $port, $existingIpPermission["IpRanges"]);
                    $io->comment("revoked all existing permissions");
                }
                $cidrCollection[] = $cidr;
                $this->setRule($client, $groupId, $port, $cidrCollection, $description);
                $io->success("Created new permissions");
            }
        }

        $io->success("Successfully updated security group");
        return self::SUCCESS;
    }

    protected function getSecurityGroupRules(Ec2Client $client, string $groupId): array
    {
        $response = $client->describeSecurityGroups([
            "GroupIds" => [$groupId],
            "Query" => "SecurityGroups[0].IpPermissions",
        ]);

        $securityGroup = $response->get("SecurityGroups");

        if (isset($securityGroup[0]["IpPermissions"])) {
            return $securityGroup[0]["IpPermissions"];
        }

        return [];
    }

    protected function hasRule(array $existingRule, string $cidr): bool
    {
        foreach ($existingRule["IpRanges"] as $ipRange) {
            if ($ipRange["CidrIp"] === $cidr) {
                return true;
            }
        }

        return false;
    }

    protected function getExistingCidrCollection(array $existingRule, int $port, string $description): array
    {
        $cidrCollection = [];
        foreach ($existingRule["IpRanges"] as $ipRange) {
            if (isset($ipRange["Description"]) && $ipRange["Description"] !== $description) {
                $cidrCollection[] = $ipRange["CidrIp"];
            }
        }

        return $cidrCollection;
    }

    protected function getIpPermissionsByPort(array $existingRules, int $port): array
    {
        foreach ($existingRules as $existingRule) {
            if ($existingRule["ToPort"] === $port && $existingRule["FromPort"] === $port) {
                return $existingRule;
            }
        }

        return [];
    }

    protected function revokeRule(Ec2Client $client, string $groupId, int $port, array $ipRanges): void
    {
        $client->revokeSecurityGroupIngress([
            "GroupId" => $groupId,
            "IpPermissions" => [
                [
                    "FromPort" => $port,
                    "ToPort" => $port,
                    "IpProtocol" => "tcp",
                    "IpRanges" => $ipRanges,
                ],
            ],
        ]);
    }

    protected function setRule(Ec2Client $client, string $groupId, int $port, array $cidrCollection, string $description): void
    {
        $ipRanges = array_map(function (string $cidr) use ($cidrCollection, $description): array {
            return [
                "CidrIp" => $cidr,
                "Description" => $description,
            ];
        }, $cidrCollection);

        $client->authorizeSecurityGroupIngress([
            "GroupId" => $groupId,
            "IpPermissions" => [
                [
                    "FromPort" => $port,
                    "ToPort" => $port,
                    "IpProtocol" => "tcp",
                    "IpRanges" => $ipRanges,
                ],
            ],
        ]);
    }
}
