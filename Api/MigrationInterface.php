<?php

namespace SomethingDigital\Migration\Api;

use Magento\Framework\Setup\SetupInterface;

interface MigrationInterface
{
    /**
     * Execute the migration.
     *
     * Do not call startSetup/endSetup.  They are called automatically.
     *
     * @param SetupInterface $setup Magento setup interface.
     * @throws \Exception Throws any exception on failure.
     * @return void
     */
    public function execute(SetupInterface $setup);
}
