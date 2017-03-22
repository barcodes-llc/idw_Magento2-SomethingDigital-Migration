<?php

namespace SomethingDigital\Migration\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        // Can't use a migration to add this, at least not yet.
        $this->addMigrationTable($setup);

        $setup->endSetup();
    }

    protected function addMigrationTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('sd_migration'))
            ->addColumn(
                'migration_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Migration ID'
            )
            ->addColumn(
                'type',
                Table::TYPE_TEXT,
                16,
                ['nullable' => false],
                'Migration Type'
            )
            ->addColumn(
                'module',
                Table::TYPE_TEXT,
                50,
                ['nullable' => false],
                'Module'
            )
            ->addColumn(
                'name',
                Table::TYPE_TEXT,
                64,
                ['nullable' => false],
                'Name'
            )
            ->addColumn(
                'status',
                Table::TYPE_TEXT,
                16,
                ['nullable' => false],
                'Status'
            )
            ->setComment('Migrations')
            ->addIndex(
                $setup->getIdxName('sd_migration', ['type', 'module', 'status']),
                ['type', 'module', 'status']
            )
            ->addIndex(
                $setup->getIdxName('sd_migration', ['type', 'module', 'name'], AdapterInterface::INDEX_TYPE_UNIQUE),
                ['type', 'module', 'name'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            );
        $setup->getConnection()->createTable($table);
    }
}
