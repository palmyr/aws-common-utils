<?php

declare(strict_types=1);

namespace Palmyr\App\Model;

interface AwsProfileModelInterface
{
    public function getProfile(): string;

    public function get(string $key): ?string;

    public function sessionIsValid(): bool;

    public function set(string $key, string $value): AwsProfileModelInterface;
}
