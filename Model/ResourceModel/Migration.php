<?php

namespace SomethingDigital\Migration\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use SomethingDigital\Migration\Model\Migration\Status as MigrationStatus;

class Migration extends AbstractDb
{
    /**
     * @var string MySQL lock name prefix.
     *
     * Note: this is database server global.
     * The table prefix and a hash of the path are also added.
     */
    const LOCK_PREFIX = 'SD_MIGRATION-';

    /**
     * @var int Seconds to delay (wait for upgrade finish) before showing an error.
     */
    const LOCK_TIMEOUT = 30;

    protected function _construct()
    {
        $this->_init(
            'sd_migration',
            'migration_id'
        );
    }

    public function getDoneMigrations($type, $module)
    {
        $migrations = $this->getAllMigrations($type, $module);
        foreach ($migrations as $name => $status) {
            if ($status != MigrationStatus::DONE) {
                unset($migrations[$name]);
            }
        }

        return $migrations;
    }

    public function getAllMigrations($type, $module)
    {
        $select = $this->getConnection()->select();
        $select->from($this->getMainTable(), ['name', 'status']);
        $select->where('type = :type');
        $select->where('module = :module');
        return $this->getConnection()->fetchPairs($select, [
            ':type' => $type,
            ':module' => $module,
        ]);
    }

    public function getLatestMigration()
    {
        $select = $this->getConnection()->select();
        $select->from($this->getMainTable(), ['type', 'module', 'name', 'status']);
        $select->order('migration_id DESC');
        $select->limit(1, 0);
        return $this->getConnection()->fetchRow($select);
    }

    public function markMigration($type, $module, $name, $status)
    {
        $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            [
                'type' => $type,
                'module' => $module,
                'name' => $name,
                'status' => $status,
            ],
            ['status']
        );
    }

    public function lockMigrations($module)
    {
        // Note: the lock will be released if the MySQL connection is dropped, too.
        $result = $this->getConnection()->query('
            SELECT GET_LOCK(:name, :timeout)', [
                ':name' => $this->prefixedLockName($module),
                ':timeout' => static::LOCK_TIMEOUT,
            ])
            ->fetchColumn();
        return !empty($result);
    }

    public function unlockMigrations($module)
    {
        $result = $this->getConnection()->query('
            SELECT RELEASE_LOCK(:name)', [
                ':name' => $this->prefixedLockName($module),
            ])->fetchColumn();
        return !empty($result);
    }

    protected function prefixedLockName($resource)
    {
        // $this->getMainTable() has the prefix in it.
        $prefix = static::LOCK_PREFIX . $this->getMainTable();
        // Let's also add the path, in case of multiple sites using the same db server.
        // This assumes that separate servers will use the same path.
        $instance = substr(md5(realpath(__FILE__)), 0, 10);
        return $prefix . '_' . $instance . '_';
    }
}