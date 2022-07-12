<?php declare(strict_types=1);


namespace Palmyr\App\Service;


use Palmyr\App\Exception\AWSResourceNotFoundException;
use Palmyr\App\Holder\SdkHolderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class InstanceIpService implements InstanceIpServiceInterface
{

    protected SdkHolderInterface $sdkHolder;

    protected PropertyAccessorInterface $propertyAccessor;

    protected LoggerInterface $logger;

    public function __construct(
        SdkHolderInterface $sdkHolder,
        PropertyAccessorInterface $propertyAccessor,
        LoggerInterface $logger
    )
    {
        $this->sdkHolder = $sdkHolder;
        $this->propertyAccessor = $propertyAccessor;
        $this->logger = $logger;
    }

    public function getByInstanceId(string $instanceId, bool $public = true): string
    {

        $result = $this->sdkHolder->getSdk()->createEc2()->describeInstances([
            "InstanceIds" => [$instanceId]
        ]);
        $reservations = $result->get('Reservations');

        $instanceDataCollection = [];
        foreach ( $reservations as $reservation ) {
            if ( array_key_exists("Instances", $reservation) ) {
                $instanceDataCollection = array_merge($instanceDataCollection, $reservation["Instances"]);
            }
        }

        if ( count($instanceDataCollection) < 1 ) {
            throw new \OutOfBoundsException('No instance was found');
        }

        $instanceData = reset($instanceDataCollection);

        if ( $ip = $this->getIpFromInstanceData($instanceData, $public) ) {
            return $ip;
        }

        throw new \RuntimeException("The instance does not have a ip address");

    }

    public function getByAutoscalingGroupName(string $autoScalingGroupName, bool $public = true, int $key = self::FIRST_INSTANCE): string
    {
        $autoScalingGroup = $this->getAutoScalingGroupByName($autoScalingGroupName);

        foreach ( $autoScalingGroup["Instances"] as $instance ) {
            try {
                if ( $instance["HealthStatus"] === "Healthy" ) {
                    return $this->getByInstanceId($instance["InstanceId"], $public);
                }
            } catch ( \Exception $e ) {
                $this->logger->error($e->getMessage());
            }
        }

        throw new AWSResourceNotFoundException("Could not load an instance from the autoscaling group");
    }

    protected function getIpFromInstanceData(array $instanceData, bool $public = true ): ?string
    {
        if ( $public ) {
            return $this->propertyAccessor->getValue($instanceData, "[PublicIpAddress]");
        }
        return $this->propertyAccessor->getValue($instanceData, "[PrivateIpAddress]");
    }

    /**
     * @param string $autoScalingGroupName
     * @return array
     * @throws AWSResourceNotFoundException
     */
    private function getAutoScalingGroupByName(string $autoScalingGroupName): array
    {
        $autoScalingClient = $this->sdkHolder->getSdk()->createAutoScaling();

        $result = $autoScalingClient->describeAutoScalingGroups([
            'AutoScalingGroupNames' => [
                $autoScalingGroupName,
            ],
        ]);

        $autoScalingGroupCollection = $result->get("AutoScalingGroups");

        if ( count($autoScalingGroupCollection) < 0 ) {
            throw new AWSResourceNotFoundException("Failed to load auto scaling group");
        }

        return reset($autoScalingGroupCollection);
    }
}
