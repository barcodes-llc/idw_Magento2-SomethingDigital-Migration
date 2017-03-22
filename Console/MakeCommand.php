<?php

namespace SomethingDigital\Migration\Console;

use SomethingDigital\Migration\Model\Migration\Christener;
use SomethingDigital\Migration\Model\Migration\Generator;
use SomethingDigital\Migration\Model\Migration\Locator;
use SomethingDigital\Migration\Model\Setup\Generator as SetupGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCommand extends Command
{
    protected $christener;
    protected $generator;
    protected $locator;
    protected $setupGenerator;

    public function __construct(
        Christener $christener,
        Generator $generator,
        Locator $locator,
        SetupGenerator $setupGenerator
    ) {
        parent::__construct(null);

        $this->christener = $christener;
        $this->generator = $generator;
        $this->locator = $locator;
        $this->setupGenerator = $setupGenerator;
    }

    protected function configure()
    {
        $this->setName('migrate:make');
        $this->setDescription('Generate a migration class file.');

        $this->addOption('module', null, InputOption::VALUE_REQUIRED, 'Name of module, i.e. Vendor_Mod');
        $this->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Type: data or schema', 'data');
        $this->addArgument('name', InputArgument::REQUIRED, 'Name to generate');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // In case it doesn't exist yet, let's create the template.
        if ($this->generateRecurring($input->getOption('module'), $input->getOption('type'))) {
            $output->writeln('Created <info>Setup class</info>');
        }

        $filename = $this->generateMigration($input->getOption('module'), $input->getOption('type'), $input->getArgument('name'));
        $output->writeln('Created <info>' . $filename . '</info>');

        return 0;
    }

    protected function generateMigration($module, $type, $name)
    {
        $name = $this->christener->christen($name);
        $filePath = $this->locator->getFilesPath($module, $type);
        $namespace = $this->locator->getClassNamespacePath($module, $type);

        $this->generator->create($namespace, $filePath, $name);

        return $filePath . '/' . $name . '.php';
    }

    protected function generateRecurring($module, $type)
    {
        if ($this->setupGenerator->exists($module, $type)) {
            // Don't need to generate anything.
            return false;
        }

        $this->setupGenerator->create($module, $type);
        return true;
    }
}
