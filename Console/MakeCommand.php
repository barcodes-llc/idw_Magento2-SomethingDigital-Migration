<?php

namespace SomethingDigital\Migration\Console;

use SomethingDigital\Migration\Model\Migration\ChristenerFactory;
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
    /**
     * @var ChristenerFactory
     */
    protected $christenerFactory;

    /**
     * @var Christener
     */
    protected $christener;

    /**
     * @var Locator
     */
    protected $locator;

    /**
     * @var SetupGenerator
     */
    protected $setupGenerator;

    /**
     * @var InputParserPool
     */
    protected $inputParserPool;

    /**
     * @var MigrationGeneratorPool
     */
    protected $migrationGeneratorPool;

    /**
     * MakeCommand constructor.
     * @param ChristenerFactory $christenerFactory
     * @param Locator $locator
     * @param SetupGenerator $setupGenerator
     * @param InputParserPool $inputParserPool
     * @param MigrationGeneratorPool $migrationGeneratorPool
     */
    public function __construct(
        ChristenerFactory $christenerFactory,
        Locator $locator,
        SetupGenerator $setupGenerator,
        InputParserPool $inputParserPool,
        MigrationGeneratorPool $migrationGeneratorPool
    ) {
        parent::__construct(null);

        $this->christenerFactory = $christenerFactory;
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
        $this->addOption('dry', null, InputOption::VALUE_NONE, 'Skip actual class creation');
        $this->addArgument('name', InputArgument::REQUIRED, 'Name to generate');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
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

    /**
     * @param \Magento\Framework\DataObject $options
     * @return string
     */
    protected function generateMigration($options)
    {
        $name = $this->getChristener()->christen($options->getName());
        $filePath = $this->locator->getFilesPath($options->getModule(), $options->getType());
        $namespace = $this->locator->getClassNamespacePath($options->getModule(), $options->getType());

        $migrationGenerator = $this->migrationGeneratorPool->get($options->getGenerator());
        $migrationGenerator->create($namespace, $filePath, $name, $options);

        return $filePath . '/' . $name . '.php';
    }

    /**
     * @param \Magento\Framework\DataObject $options
     * @return bool
     */
    protected function generateRecurring($options)
    {
        if ($this->setupGenerator->exists($options)) {
            // Don't need to generate anything.
            return false;
        }

        $this->setupGenerator->create($options);
        return true;
    }

    /**
     * @return Christener
     */
    protected function getChristener()
    {
        if ($this->christener) {
            return $this->christener;
        }

        $this->christener = $this->christenerFactory->create();

        return $this->christener;
    }
}
