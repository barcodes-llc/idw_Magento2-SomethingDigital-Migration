<?php

namespace SomethingDigital\Migration\Console;

use SomethingDigital\Migration\Model\Migration\Christener;
use SomethingDigital\Migration\Model\Migration\Locator;
use SomethingDigital\Migration\Model\Setup\Generator as SetupGenerator;
use SomethingDigital\Migration\Console\Input\ParserPool as InputParserPool;
use SomethingDigital\Migration\Model\Migration\GeneratorPool as MigrationGeneratorPool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCommand extends Command
{
    protected $christener;
    protected $locator;
    protected $setupGenerator;
    protected $inputParserPool;
    protected $migrationGeneratorPool;

    public function __construct(
        Christener $christener,
        Locator $locator,
        SetupGenerator $setupGenerator,
        InputParserPool $inputParserPool,
        MigrationGeneratorPool $migrationGeneratorPool
    ) {
        parent::__construct(null);

        $this->christener = $christener;
        $this->locator = $locator;
        $this->setupGenerator = $setupGenerator;
        $this->inputParserPool = $inputParserPool;
        $this->migrationGeneratorPool = $migrationGeneratorPool;
    }

    protected function configure()
    {
        $this->setName('migrate:make');
        $this->setDescription('Generate a migration class file.');

        $this->addOption('module', null, InputOption::VALUE_REQUIRED, 'Name of module, i.e. Vendor_Mod');
        $this->addOption('create-from-block', null, InputOption::VALUE_OPTIONAL, 'Identifier of cms-block to create');
        $this->addOption('update-from-block', null, InputOption::VALUE_OPTIONAL, 'Identifier of cms-block to update');
        $this->addOption('create-from-page', null, InputOption::VALUE_OPTIONAL, 'Identifier of cms-page to create');
        $this->addOption('update-from-page', null, InputOption::VALUE_OPTIONAL, 'Identifier of cms-page to update');
        $this->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Type: data or schema', 'data');
        $this->addArgument('name', InputArgument::REQUIRED, 'Name to generate');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $this->inputParserPool->parse($input);
        // In case it doesn't exist yet, let's create the template.
        if ($this->generateRecurring($options)) {
            $output->writeln('Created <info>Setup class</info>');
        }
        $filename = $this->generateMigration($options);
        $output->writeln('Created <info>' . $filename . '</info>');

        return 0;
    }

    protected function generateMigration($options)
    {
        $name = $this->christener->christen($options->getName());
        $filePath = $this->locator->getFilesPath($options->getModule(), $options->getType());
        $namespace = $this->locator->getClassNamespacePath($options->getModule(), $options->getType());

        $migrationGenerator = $this->migrationGeneratorPool->get($options->getGenerator());
        $migrationGenerator->create($namespace, $filePath, $name, $options);

        return $filePath . '/' . $name . '.php';
    }

    protected function generateRecurring($options)
    {
        if ($this->setupGenerator->exists($options->getModule(), $options->getType())) {
            // Don't need to generate anything.
            return false;
        }

        $this->setupGenerator->create($options->getModule(), $options->getType());
        return true;
    }
}
