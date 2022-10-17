<?php

declare(strict_types=1);

namespace Palmyr\App\Model;

interface AwsIniModelInterface
{
    public function getProfile(string $profile): ?AwsProfileModelInterface;

    public function setProfile(AwsProfileModelInterface $profileData): AwsIniModelInterface;

    public function getData(): array;
}
