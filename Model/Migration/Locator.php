<?php

namespace SomethingDigital\Migration\Model\Migration;

use Magento\Framework\Filesystem\Directory\Read as DirRead;
use Magento\Framework\Filesystem\Directory\ReadFactory as DirReadFactory;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;

class Locator
{
    const MIGRATION_PATH_COMPONENT = 'Migration';

    protected $moduleDirReader;
    protected $dirReadFactory;

    public function __construct(ModuleDirReader $moduleDirReader, DirReadFactory $dirReadFactory)
    {
        $this->moduleDirReader = $moduleDirReader;
        $this->dirReadFactory = $dirReadFactory;
    }

    /**
     * Locate migration classes available for a module and specific type.
     *
     * Returns an array of class names, indexed by migration name.
     *
     * @param mixed $moduleName Name of Magento module, i.e. 'SomethingDigital_Migration'.
     * @param string $type 'data' or 'schema'.
     * @return string[]
     */
    public function locate($moduleName, $type)
    {
        $path = $this->getFilesPath($moduleName, $type);
        /** @var DirRead $directoryRead */
        $directoryRead = $this->dirReadFactory->create($path);
        if (!$directoryRead->isExist()) {
            return [];
        }

        // Add the \ at the end for convenience.
        $namespace = $this->getClassNamespacePath($moduleName, $type) . '\\';
        $migrations = [];
        foreach ($directoryRead->read() as $entry) {
            // The name is simply the filename, without .php.  It must end in .php, though.
            $ending = substr($entry, -4);
            if (strtolower($ending) !== '.php' || $directoryRead->isDirectory($entry)) {
                continue;
            }

            $name = basename($entry, $ending);
            $migrations[$name] = $namespace . $name;
        }

        return $migrations;
    }

    /**
     * Determine the module path for migration classes.
     *
     * @param mixed $moduleName Name of Magento module, i.e. 'SomethingDigital_Migration'.
     * @param string $type 'data' or 'schema'.
     * @return string
     */
    public function getFilesPath($moduleName, $type)
    {
        $moduleDir = $this->moduleDirReader->getModuleDir('', $moduleName);
        if (!$moduleDir) {
            throw new \UnexpectedValueException('No module dir for module: ' . $moduleName);
        }
        return $moduleDir . '/' . static::MIGRATION_PATH_COMPONENT . '/' . $this->getTypePathComponent($type);
    }

    /**
     * Determine the module class namespace for migration classes.
     *
     * @param mixed $moduleName Name of Magento module, i.e. 'SomethingDigital_Migration'.
     * @param string $type 'data' or 'schema'.
     * @return string
     */
    public function getClassNamespacePath($moduleName, $type)
    {
        // From Magento\Setup\Model\Installer's logic.
        $namespace = str_replace('_', '\\', $moduleName);
        return $namespace . '\\' . static::MIGRATION_PATH_COMPONENT . '\\' . $this->getTypePathComponent($type);
    }

    /**
     * Translate a migration type to path component.
     *
     * @param string $type 'data' or 'schema'.
     * @throws \UnexpectedValueException Invalid $type.
     * @return string
     */
    protected function getTypePathComponent($type)
    {
        if ($type === 'data') {
            return 'Data';
        } elseif ($type === 'schema') {
            return 'Schema';
        }

        throw new \UnexpectedValueException('Unexpected migration type parameter: ' . $type);
    }
}
