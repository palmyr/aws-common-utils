<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Palmyr\SymfonyAws\Model\AwsProfileModel;
use Palmyr\SymfonyAws\Model\AwsProfileModelInterface;
use Palmyr\SymfonyAws\Factory\SdkFactoryInterface;
use Palmyr\SymfonyAws\Service\AwsIniFileServiceInterface;
use Aws\Sts\Exception\StsException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class MFALoginCommand extends AbstractAWSConfigurationCommand
{

    public const AWS_INI_FILENAME_MFA = AwsIniFileServiceInterface::AWS_INI_FILENAME . '_mfa';

    public const SESSION_DURATION = 129600;

    protected AwsIniFileServiceInterface $iniFileService;

    protected SdkFactoryInterface $sdkFactory;

    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        AwsIniFileServiceInterface $iniFileService,
        SdkFactoryInterface $sdkFactory,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->iniFileService = $iniFileService;
        $this->sdkFactory = $sdkFactory;
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

        if (!$mfaProfile = $this->iniFileService->loadProfile((string)$input->getOption("profile"), self::AWS_INI_FILENAME_MFA)) {
            $io->error("Profile not found.");
            return self::INVALID;
        }

        if (!$mfaProfile->get("mfa_serial_number")) {
            $io->error("The profile config does not contain a mfa serial number");
            return self::INVALID;
        }

        $profile = $this->verifyLogin($mfaProfile, $io);

        $io->success(sprintf("Successfully logged in, the token will expire at %s.", $profile->getSessionTokenExpiresAt()->format(\DATE_ATOM)));

        return self::SUCCESS;
    }

    protected function verifyLogin(AwsProfileModelInterface $mfaProfile, SymfonyStyle $io): AwsProfileModelInterface
    {
        if ( !$profile = $this->iniFileService->loadProfile($mfaProfile->getProfile()) ) {
            $profile = $this->login($mfaProfile, $io);
            $this->iniFileService->saveProfile($profile);
            return $profile;
        }

        if ( $this->sessionIsValid($profile) ) {
            return $profile;
        }

        return $this->login($mfaProfile, $io);

    }

    protected function login(AwsProfileModelInterface $mfaProfile, SymfonyStyle $io): AwsProfileModelInterface
    {

        $options = [
            "credentials" => [
                "key" => $mfaProfile->get("aws_access_key_id"),
                "secret" => $mfaProfile->get("aws_secret_access_key"),
            ],
            "region" => $mfaProfile->getRegion(),
        ];

        $stsClient = $this->sdkFactory->build($options)->createSts();

        $result = null;
        $loggedIn = false;
        while (!$loggedIn) {
            $token = $io->ask("MFA token");
            try {
                $result = $stsClient->getSessionToken([
                    "SerialNumber" => $mfaProfile->get("mfa_serial_number"),
                    "TokenCode" => $token,
                    "DurationSeconds" => self::SESSION_DURATION,
                ]);
                $loggedIn = true;
            } catch (StsException $e) {
                if ($e->getAwsErrorCode() === "AccessDenied") {
                    $io->error("Failed to authenticate, please retry. " . $e->getAwsErrorMessage());
                } else {
                    throw $e;
                }
            }
        }

        $credentials = $result->get("Credentials");

        $expiresAt = \DateTimeImmutable::createFromInterface($this->propertyAccessor->getValue($credentials, "[Expiration]"));

        $region = $this->sdkFactory->getRegion() ?? $mfaProfile->getRegion();

        $profile = new AwsProfileModel($mfaProfile->getProfile(), [
            "aws_access_key_id" => $this->propertyAccessor->getValue($credentials, "[AccessKeyId]"),
            "aws_secret_access_key" => $this->propertyAccessor->getValue($credentials, "[SecretAccessKey]"),
            "aws_session_token" => $this->propertyAccessor->getValue($credentials, "[SessionToken]"),
            "aws_session_token_expiration" => $expiresAt->format(\DateTimeInterface::ATOM),
            "region" => $region,
        ]);

        $this->iniFileService->saveProfile($profile, AwsIniFileServiceInterface::AWS_INI_FILENAME);

        return $profile;
    }

    protected function sessionIsValid(AwsProfileModelInterface $profile): bool
    {
        if ($expiresAt = $profile->getSessionTokenExpiresAt()) {
            if ($expiresAt > new \DateTimeImmutable()) {
                return true;
            }
        }

        return false;
    }
}
