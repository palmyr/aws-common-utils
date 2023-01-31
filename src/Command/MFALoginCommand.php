<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Aws\Sts\StsClient;
use Palmyr\App\Model\AwsProfileModel;
use Palmyr\App\Model\AwsProfileModelInterface;
use Palmyr\App\Service\AwsIniFileServiceInterface;
use Aws\Sdk;
use Aws\Sts\Exception\StsException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Palmyr\App\Enum\ErrorMessages;

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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $profile = (string)$input->getOption("profile");

        $data = $this->iniFileService->parseAwsIni(AwsIniFileServiceInterface::AWS_INI_FILENAME_MFA);

        if (!$profileData = $data->getProfile($profile)) {
            $io->error("The profile [{$profile}] could not be found");
            return self::INVALID;
        }

        $stsClient = $this->buildSDK($profileData)->createSts();

        if (!$profileData->get("mfa_serial_number")) {
            $io->error("The profile config does not contain a mfa serial number");
            return self::INVALID;
        }

        $expiresAt = $this->login($stsClient, $profileData, $io);

        $io->success(sprintf("Successfully logged in, the token will expire at %s.", $expiresAt->format(\DATE_ATOM)));

        return self::SUCCESS;
    }

    /**
     * @param AwsProfileModelInterface $profileData
     * @return Sdk
     */
    protected function buildSDK(AwsProfileModelInterface $profileData): Sdk
    {
        return new Sdk([
            "version" => "latest",
            "credentials" => [
                "key" => $profileData->get("aws_access_key_id"),
                "secret" => $profileData->get("aws_secret_access_key"),
            ],
            "region" => $profileData->get("region"),
        ]);
    }

    protected function login(StsClient $stsClient, AwsProfileModelInterface $profileData, SymfonyStyle $io): \DateTimeImmutable
    {
        $data = $this->iniFileService->parseAwsIni(AwsIniFileServiceInterface::AWS_INI_FILENAME);

        if (($previousProfileData = $data->getProfile($profileData->getProfile())) && $previousProfileData->sessionIsValid()) {
            return new \DateTimeImmutable($previousProfileData->get("aws_session_token_expiration"));
        }

        $result = null;
        $loggedIn = false;
        while (!$loggedIn) {
            $token = $io->ask("MFA token");
            try {
                $result = $stsClient->getSessionToken([
                    "SerialNumber" => $profileData->get("mfa_serial_number"),
                    "TokenCode" => $token,
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

        $expiresAt = \DateTimeImmutable::createFromInterface($this->propertyAccessor->getValue($credentials, "[Expiration]"));

        $temporaryProfileData = new AwsProfileModel($profileData->getProfile(), [
            "aws_access_key_id" => $this->propertyAccessor->getValue($credentials, "[AccessKeyId]"),
            "aws_secret_access_key" => $this->propertyAccessor->getValue($credentials, "[SecretAccessKey]"),
            "aws_session_token" => $this->propertyAccessor->getValue($credentials, "[SessionToken]"),
            "aws_session_token_expiration" => $expiresAt->format(\DateTimeInterface::ATOM),
            "region" => $profileData->get("region"),
        ]);

        $data->setProfile($temporaryProfileData);

        $this->iniFileService->writeAwsIni($data->getData(), AwsIniFileServiceInterface::AWS_INI_FILENAME);

        return $expiresAt;
    }
}
