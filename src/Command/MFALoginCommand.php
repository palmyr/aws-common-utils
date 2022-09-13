<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Palmyr\App\Exception\SdkBuildException;
use Palmyr\App\Service\AwsIniFileServiceInterface;
use Aws\Sdk;
use Aws\Sts\Exception\StsException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class MFALoginCommand extends AbstractAWSConfigurationCommand
{
    protected AwsIniFileServiceInterface $iniFileService;

    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        AwsIniFileServiceInterface $iniFileService,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->iniFileService = $iniFileService;
        $this->propertyAccessor = $propertyAccessor;
        parent::__construct("mfa:login");
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription("Generate credentials using mfa");
    }

    /**
     * @param InputInterface $input
     * @return Sdk
     * @throws SdkBuildException
     */
    protected function buildSDK(InputInterface $input): Sdk
    {
        $profile = (string)$input->getOption("profile");

        $data = $this->iniFileService->parseAwsIni(AwsIniFileServiceInterface::AWS_INI_FILENAME_MFA);

        if (!$this->propertyAccessor->getValue($data, "[{$profile}]")) {
            throw new SdkBuildException("Could not find the requested profile.");
        }

        $region = $this->propertyAccessor->getValue($data, "[{$profile}][region]");

        if ($input->getOption("region")) {
            $region = $input->getOption("region");
        }

        return new Sdk([
            "version" => "latest",
            "credentials" => [
                "key" => $this->propertyAccessor->getValue($data, "[{$profile}][aws_access_key_id]"),
                "secret" => $this->propertyAccessor->getValue($data, "[{$profile}][aws_secret_access_key]"),
            ],
            "region" => $region,
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $profile = (string)$input->getOption("profile");

        $data = $this->iniFileService->parseAwsIni();
        $mfaData = $this->iniFileService->parseAwsIni("credentials_mfa");

        if (!$serialNumber = $this->propertyAccessor->getValue($mfaData, "[{$profile}][mfa_serial_number]")) {
            $io->error("The profile config does not contain a mfa serial number");
            return self::INVALID;
        }

        if ($currentTokenExpirationString = (string)$this->propertyAccessor->getValue($data, "[{$profile}][aws_session_token_expiration]")) {
            $currentTokenExpirationDate = new \DateTimeImmutable($currentTokenExpirationString);
            if ($currentTokenExpirationDate > new \DateTimeImmutable()) {
                $io->success(sprintf("Current token is still valid [Expiration: %s ]", $currentTokenExpirationDate->format(\DateTimeInterface::ATOM)));
                return self::SUCCESS;
            }
        }

        $region = (string)$this->propertyAccessor->getValue($mfaData, "[{$profile}][region]");
        if ($input->getOption("region")) {
            $region = $input->getOption("region");
        }

        $data[$profile] = $profileData = $this->login($io, $input, $serialNumber, $region, $profile);

        $this->iniFileService->writeAwsIni($data);

        $io->success(sprintf("Successfully logged in, the token will expire at %s.", $profileData["aws_session_token_expiration"]));

        return self::SUCCESS;
    }

    protected function login(SymfonyStyle $io, InputInterface $input, string $serialNumber, string $region, string $profile): array
    {
        $stsClient = $this->buildSDK($input)->createSts();

        $loggedIn = false;
        while (!$loggedIn) {
            try {
                $result = $stsClient->getSessionToken([
                    "SerialNumber" => $serialNumber,
                    "TokenCode" => $io->ask("MFA token")
                ]);
                $loggedIn = true;
            } catch (StsException $e) {
                if ($e->getAwsErrorCode() === "AccessDenied") {
                    $io->error("Failed to authenticate, please retry.");
                } else {
                    throw $e;
                }
            }
        }

        $credentials = $result->get("Credentials");

        $expiration = \DateTimeImmutable::createFromInterface($this->propertyAccessor->getValue($credentials, "[Expiration]"));

        return [
            "aws_access_key_id" => $this->propertyAccessor->getValue($credentials, "[AccessKeyId]"),
            "aws_secret_access_key" => $this->propertyAccessor->getValue($credentials, "[SecretAccessKey]"),
            "aws_session_token" => $this->propertyAccessor->getValue($credentials, "[SessionToken]"),
            "aws_session_token_expiration" => $expiration->format(\DateTimeInterface::ATOM),
            "region" => $region,
        ];
    }
}
