<?php

namespace SomethingDigital\Migration\Model\Setup;

use Magento\Framework\Filesystem\Directory\Read as DirRead;
use Magento\Framework\Filesystem\Directory\ReadFactory as DirReadFactory;
use Magento\Framework\Filesystem\Directory\WriteFactory as DirWriteFactory;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;
use SomethingDigital\Migration\Model\AbstractGenerator;

class Generator extends AbstractGenerator
{
    protected $dirReadFactory;
    protected $moduleDirReader;

    public function __construct(
        DirReadFactory $dirReadFactory,
        DirWriteFactory $dirWriteFactory,
        ModuleDirReader $moduleDirReader
    ) {
        parent::__construct($dirWriteFactory);
        $this->dirReadFactory = $dirReadFactory;
        $this->moduleDirReader = $moduleDirReader;
    }

    public function exists($moduleName, $type)
    {
        $path = $this->getPath($moduleName);
        $filename = $this->getTypePathComponent($type) . '.php';

        /** @var DirRead $dirRead */
        $dirRead = $this->dirReadFactory->create($path);
        return $dirRead->isExist($filename);
    }

    public function create($moduleName, $type)
    {
        $filePath = $this->getPath($moduleName);
        $namespace = $this->getClassNamespacePath($moduleName);
        $name = $this->getTypePathComponent($type);

        if ($type === 'data') {
            $code = $this->makeDataCode($namespace, $name, $moduleName);
        } elseif ($type === 'schema') {
            $code = $this->makeSchemaCode($namespace, $name, $moduleName);
        } else {
            // Yes, we already checked this in getTypePathComponent,
            // but we want an exception anywhere we forgot to add a type.
            throw new \UnexpectedValueException('Unexpected migration type parameter: ' . $type);
        }

        $this->writeCode($filePath, $name, $code);
    }

    protected function getPath($moduleName)
    {
        $moduleDir = $this->moduleDirReader->getModuleDir('', $moduleName);
        return $moduleDir . '/Setup';
    }

    protected function getClassNamespacePath($moduleName)
    {
        // From Magento\Setup\Model\Installer's logic.
        $namespace = str_replace('_', '\\', $moduleName);
        return $namespace . '\\Setup';
    }

    protected function getTypePathComponent($type)
    {
        if ($type === 'data') {
            return 'RecurringData';
        } elseif ($type === 'schema') {
            return 'Recurring';
        }

        throw new \UnexpectedValueException('Unexpected migration type parameter: ' . $type);
    }

    protected function makeDataCode($namespace, $name, $moduleName)
    {
        return '<?php

namespace ' . $namespace . ';

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\InstallDataInterface;
use SomethingDigital\Migration\Api\MigratorInterface;

class ' . $name . ' implements InstallDataInterface
{
    protected $migrator;

    public function __construct(MigratorInterface $migrator)
    {
        $this->migrator = $migrator;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->migrator->execute($setup, ' . var_export($moduleName, true) . ', \'data\');
    }
}
';
    }

    protected function makeSchemaCode($namespace, $name, $moduleName)
    {
        return '<?php

namespace ' . $namespace . ';

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use SomethingDigital\Migration\Api\MigratorInterface;

class ' . $name . ' implements InstallSchemaInterface
{
    protected $migrator;

    public function __construct(MigratorInterface $migrator)
    {
        $this->migrator = $migrator;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->migrator->execute($setup, ' . var_export($moduleName, true) . ', \'schema\');
    }
}
';
    }
}