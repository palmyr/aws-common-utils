<?php declare(strict_types=1);

namespace Palmyr\App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

abstract class AbstractAWSConfigurationCommand extends Command
{

    protected function configure()
    {
        parent::configure();
        $this->addOption("profile", "p", InputOption::VALUE_REQUIRED, "The profile to use");
        $this->addOption("region", "r", InputOption::VALUE_OPTIONAL, "The region to use");
    }

}