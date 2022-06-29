<?php

declare(strict_types=1);

namespace Palmyr\App\Command;

use Aws\Credentials\CredentialProvider;
use Palmyr\App\Holder\SdkHolderInterface;
use Palmyr\App\Service\AwsIniFileServiceInterface;
use Aws\Sdk;
use Aws\Sts\Exception\StsException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class MFALoginCommand extends AbstractAWSCommand
{

    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        SdkHolderInterface $sdkHolder,
        AwsIniFileServiceInterface $iniFileService,
        string $name = null)
    {
        parent::__construct($sdkHolder, $iniFileService, 'mfa:login');
    }

    protected function configure()
    {
        parent::configure();
    }

    protected function prepareSDK(InputInterface $input): void
    {

        $profile = (string)$input->getOption("profile");

        $data = $this->iniFileService->parseAwsIni();

        if ( !array_key_exists($profile, $data) ) {
            throw new \RuntimeException("Failed to find profile");
        }
        $profileData = $data[$profile];

        $region = $profileData["region"];

        if ($input->getOption("region")) {
            $region = $input->getOption("region");
        }

        $sdk = new Sdk([
            "version" => "latest",
            "credentials" => [
                "key" => $profileData["aws_access_key_id"],
                "secret" => $profileData["aws_secret_access_key"],
            ],
            "region" => $region,
        ]);

        $this->sdkHolder->setSdk($sdk);
    }

    protected function runCommand(InputInterface $input, SymfonyStyle $io): int
    {
        $profile = $input->getOption('profile');

        $data = $this->iniFileService->parseAwsIni();
        $mfaData = $this->iniFileService->parseAwsIni('credentials_mfa');

        if (array_key_exists($profile, $mfaData) && isset($mfaData[$profile]['mfa_serial_number'])) {
            $serialNumber = $mfaData[$profile]['mfa_serial_number'];
        } else {
            $io->error('The profile config does not contain a mfa serial number');
            return self::INVALID;
        }

        if (isset($data[$profile]) && is_array($data[$profile]) && $profileData = $data[$profile]) {
            if (isset($profileData['aws_session_token_expiration'])) {
                $expiration = new \DateTime($profileData['aws_session_token_expiration']);
                if ($expiration > new \DateTime()) {
                    $io->success(sprintf('Current token is still valid [Expiration: %s ]', $expiration->format(\DATE_ATOM)));
                    return self::SUCCESS;
                }
            }
        }

        $stsClient = $this->getSdk()->createSts();

        $loggedIn = false;
        while (!$loggedIn) {
            try {
                $result = $stsClient->getSessionToken([
                    'SerialNumber' => $serialNumber,
                    'TokenCode' => $io->ask('MFA token'),
                ]);
                $loggedIn = true;
            } catch (StsException $e) {
                if ($e->getAwsErrorCode() === 'AccessDenied') {
                    $io->error('Failed to authenticate');
                } else {
                    throw $e;
                }
            }
        }

        $credentials = $result->get('Credentials');

        /** @var \DateTimeInterface $expiration */
        $expiration = $credentials['Expiration'];

        $data[$profile] = [
            'aws_access_key_id' => $credentials['AccessKeyId'],
            'aws_secret_access_key' => $credentials['SecretAccessKey'],
            'aws_session_token' => $credentials['SessionToken'],
            'aws_session_token_expiration' => $expiration->format(\DATE_ATOM),
            "region" => $this->propertyAccessor->getValue($data, '['.$profile.'][region]'),
        ];

        $this->iniFileService->writeAwsIni($data);

        $io->success(sprintf('Successfully logged in, the token will expire at %s.', $expiration->format(\DateTimeInterface::ATOM)));

        return self::SUCCESS;
    }
}
