<?php

namespace SomethingDigital\Migration\Api;

use Magento\Framework\Setup\SetupInterface;

interface MigratorInterface
{
    /**
     * Execute all pending migrations for a particular module.
     *
     * @param SetupInterface $setup Magento setup class.
     * @param mixed $moduleName Module name to execute migrations for.
     * @param string $type 'schema' or 'data' to execute.
     */
    public function execute(SetupInterface $setup, $moduleName, $type);
}
