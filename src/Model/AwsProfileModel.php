<?php

declare(strict_types=1);

namespace Palmyr\App\Model;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class AwsProfileModel implements AwsProfileModelInterface
{
    protected string $profile;

    protected array $data;

    public function __construct(
        string $profile,
        array $data
    ) {
        $this->profile = $profile;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getProfile(): string
    {
        return $this->profile;
    }

    public function get(string $key): ?string
    {
        if (array_key_exists($key, $this->data)) {
            return (string)$this->data[$key];
        }

        return null;
    }

    public function set(string $key, string $value): AwsProfileModelInterface
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function sessionIsValid(): bool
    {
        if ($currentTokenExpirationString = (string)$this->get("aws_session_token_expiration")) {
            $currentTokenExpirationDate = new \DateTimeImmutable($currentTokenExpirationString);
            if ($currentTokenExpirationDate > new \DateTimeImmutable()) {
                return true;
            }
        }

        return false;
    }
}
