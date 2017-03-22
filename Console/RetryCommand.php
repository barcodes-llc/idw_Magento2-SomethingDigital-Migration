<?php

namespace SomethingDigital\Migration\Console;

use SomethingDigital\Migration\Model\Migration\Status;
use SomethingDigital\Migration\Model\ResourceModel\Migration as MigrationResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RetryCommand extends Command
{
    protected $resource;

    public function __construct(MigrationResource $resource)
    {
        parent::__construct(null);

        $this->resource = $resource;
    }

    protected function configure()
    {
        $this->setName('migrate:retry');
        $this->setDescription('Re-execute the last migration.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $info = $this->resource->getLatestMigration();
        $this->resource->markMigration($info['type'], $info['module'], $info['name'], Status::FAILED);

        $output->writeln('Marked <info>' . $info['module'] . ' - ' . $info['name'] . '</info> (' . $info['type'] . ') for retry.');

        $this->callCommand($output, 'setup:upgrade');

        return 0;
    }

    protected function callCommand($output, $name, array $arguments = [])
    {
        $arguments['command'] = $name;
        $this->getApplication()->find($name)->run(new ArrayInput($arguments), $output);
    }
}
