<?php declare(strict_types=1);

namespace Palmyr\App\Command;

use Palmyr\App\Holder\SdkHolderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DownloadLogGroupStreamsCommand extends AbstractAWSCommand
{

    public function __construct(ContainerInterface $container, SdkHolderInterface $sdkHolder)
    {
        parent::__construct($container, $sdkHolder, "logs:download_log_group_logs");
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->addArgument("log_group_name", InputArgument::REQUIRED, "The name of the log group.")
            ->addArgument("output", InputArgument::REQUIRED, "The output file.");
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title("Starting download...");

        $client = $this->getSdk()->createCloudWatchLogs();

        $outputFile = new \SplFileObject((string)$input->getArgument("output"), "w");

        $logGroupName = (string)$input->getArgument("log_group_name");

        $logSteamCollection = $client->getIterator("DescribeLogStreams", [
            "logGroupName" => $logGroupName,
        ]);

        foreach ( $logSteamCollection as $logStream ) {
            $logStreamName = (string)$logStream["logStreamName"];
            $logEventCollection = $client->getIterator("GetLogEvents", [
                "logGroupName" => $logGroupName,
                "logStreamName" => $logStreamName,
            ]);
            foreach ( $logEventCollection as $logEvent ) {
                $outputString = $this->phaseLog($logEvent);
                $outputFile->fwrite($outputString . PHP_EOL);
                $io->comment(sprintf("Downloaded log - %s", $logStreamName));
            }
        }
    }

    protected function phaseLog(array $log): string
    {

        $log = $this->preprocessLog($log);
        return sprintf("%s - %s", $log["timestamp"], $log["message"]);
    }

    protected function preprocessLog(array $log): array
    {
        foreach (["timestamp"] as $key) {
            if ( isset($log[$key]) &&  is_int($log[$key]) ) {
                $log[$key] = (new \DateTimeImmutable())->setTimestamp($log[$key])->format(\DateTimeInterface::ATOM);
            }
        }

        return $log;
    }

}