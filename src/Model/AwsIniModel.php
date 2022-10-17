<?php

declare(strict_types=1);

namespace Palmyr\App\Model;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class AwsIniModel implements AwsIniModelInterface
{
    protected array $data;

    public function __construct(
        array $data
    ) {
        $this->data = $data;
    }

    public function getProfile(string $profile): ?AwsProfileModelInterface
    {
        if (array_key_exists($profile, $this->data)) {
            return new AwsProfileModel($profile, $this->data[$profile]);
        }

        return null;
    }

    public function setProfile(AwsProfileModelInterface $profileData): AwsIniModelInterface
    {
        $this->data[$profileData->getProfile()] = $profileData->getData();

        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
