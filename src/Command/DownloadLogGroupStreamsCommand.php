<?php declare(strict_types=1);

namespace Palmyr\App\Command;

use Palmyr\App\Holder\SdkHolderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DownloadLogGroupStreamsCommand extends AbstractAWSCommand
{

    public function __construct(ContainerInterface $container, SdkHolderInterface $sdkHolder)
    {
        parent::__construct($container, $sdkHolder, "logs:download_log_group_logs");
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument("log_group_name", InputArgument::REQUIRED, "The name of the log group.")
            ->addArgument("output", InputArgument::REQUIRED, "The output file.")
            ->addOption("start", null, InputOption::VALUE_REQUIRED, "The start time.")
            ->addOption("end", null, InputOption::VALUE_REQUIRED, "The end time.");
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title("Starting download...");

        $client = $this->getSdk()->createCloudWatchLogs();

        $outputFile = new \SplFileObject((string)$input->getArgument("output"), "w");

        $logGroupName = (string)$input->getArgument("log_group_name");

        $args = [
            "logGroupName" => $logGroupName,
        ];

        foreach (["start" => "startTime", "end" => "endTime"] as $inputKey => $argKey) {
            if ( $value = $input->getOption($inputKey) ) {
                $args[$argKey] = (new \DateTimeImmutable($value))->getTimestamp() * 1000;
            }
        }

        $logSteamCollection = $client->getIterator("FilterLogEvents", $args);

        foreach ( $logSteamCollection as $logStream ) {
            $outputString = $this->phaseLog($logStream);
            $outputFile->fwrite($outputString . PHP_EOL);
            $io->comment($outputString);
        }

        return self::SUCCESS;
    }

    protected function phaseLog(array $log): string
    {

        $log = $this->preprocessLog($log);
        return sprintf("%s - [%s] %s", $log["logStreamName"], $log["timestamp"], $log["message"]);
    }

    protected function preprocessLog(array $log): array
    {
        foreach (["timestamp"] as $key) {
            if ( isset($log[$key]) &&  is_int($log[$key]) ) {
                $log[$key] = (new \DateTimeImmutable())->setTimestamp((int)($log[$key] / 1000))->format(\DateTimeInterface::ATOM);
            }
        }

        return $log;
    }

}