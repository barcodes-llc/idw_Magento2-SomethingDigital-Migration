<?php

namespace SomethingDigital\Migration;

// We create arbitrary migration classes by name, so we need this directly for that, and that only.
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Setup\SetupInterface;
use Psr\Log\LoggerInterface;
use SomethingDigital\Migration\Api\MigrationInterface;
use SomethingDigital\Migration\Api\MigratorInterface;
use SomethingDigital\Migration\Exception\LockException;
use SomethingDigital\Migration\Exception\UsageException;
use SomethingDigital\Migration\Model\Migration\Locator;
use SomethingDigital\Migration\Model\Migration\Status;
use SomethingDigital\Migration\Model\ResourceModel\Migration as MigrationResource;

class Migrator implements MigratorInterface
{
    protected $locator;
    protected $resource;
    protected $objectManager;
    protected $logger;

    protected $execSetup = null;
    protected $execModuleName = null;
    protected $execType = null;

    public function __construct(
        Locator $locator,
        MigrationResource $resource,
        LoggerInterface $logger
    ) {
        $this->locator = $locator;
        $this->resource = $resource;
        $this->logger = $logger;

        // Type-hinting the object manager seems to break some things later on, so let's not.
        // Possibly a bug in Magento 2.1.x.
        $objectManager = ObjectManager::getInstance();
        $this->objectManager = $objectManager;
    }

    /**
     * Execute all pending migrations for a particular module.
     *
     * @param SetupInterface $setup Magento setup class.
     * @param mixed $moduleName Module name to execute migrations for.
     * @param string $type 'schema' or 'data' to execute.
     */
    public function execute(SetupInterface $setup, $moduleName, $type)
    {
        $this->execSetup = $setup;
        $this->execModuleName = $moduleName;
        $this->execType = $type;

        $this->lock();
        try {
            $migrations = $this->getMigrationsToExecute();
            foreach ($migrations as $name => $class) {
                $this->executeMigration($name, $class);
            }
        } finally {
            $this->unlock();

            $this->execSetup = null;
            $this->execModuleName = null;
            $this->execType = null;
        }
    }

    /**
     * Execute the named migration through its class.
     *
     * @param string $name Migration name.
     * @param string $class Full class name of migration.
     */
    protected function executeMigration($name, $class)
    {
        $this->markMigration($name, Status::RUNNING);
        try {
            $instance = $this->makeMigrationInstance($class);

            // We call startSetup/endSetup automatically.  KISS.
            $this->execSetup->startSetup();
            $instance->execute($this->execSetup);
            $this->execSetup->endSetup();
        } catch (\Exception $e) {
            try {
                // End setup mode, if we can.
                $this->execSetup->endSetup();

                $this->markMigration($name, Status::FAILED);
            } catch (\Exception $eFailed) {
                $this->logger->error($eFailed->__toString());
            }

            throw $e;
        }

        $this->markMigration($name, Status::DONE);
    }

    /**
     * Create a validated instance of a migration class.
     *
     * @param string $class Class name.
     * @throws UsageException Class was not defined correctly.
     * @return MigrationInterface
     */
    protected function makeMigrationInstance($class)
    {
        // This also checks if the class exists.
        if (is_subclass_of($class, MigrationInterface::class)) {
            /** @var MigrationInterface $instance */
            $instance = $this->objectManager->create($class);
            return $instance;
        }

        throw new UsageException(__('Migration %1 must implement MigrationInterface.', $class));
    }

    /**
     * Retrieve a list of migration classes that should be executed.
     *
     * @return string[] Class names indexed by migration name.
     */
    protected function getMigrationsToExecute()
    {
        $available = $this->getAvailableMigrations();
        $done = $this->getDoneMigrations();
        $ready = array_diff_key($available, $done);
        // Make sure they run in order.
        ksort($ready);
        return $ready;
    }

    /**
     * Retrieve migrations available on the classes that we could run.
     *
     * Note that some may already be executed.
     *
     * @return string[] Class names indexed by migration name.
     */
    protected function getAvailableMigrations()
    {
        return $this->locator->locate($this->execModuleName, $this->execType);
    }

    /**
     * Get a list of completed migrations.
     *
     * @return string[] Statuses indexed by migration name.
     */
    protected function getDoneMigrations()
    {
        return $this->resource->getDoneMigrations($this->execType, $this->execModuleName);
    }

    /**
     * Update the specified migration's status.
     *
     * @param string $name Migration name.
     * @param string $status One of the Status::* constants.
     */
    protected function markMigration($name, $status)
    {
        $this->resource->markMigration($this->execType, $this->execModuleName, $name, $status);
    }

    /**
     * Lock a module's migrations.
     *
     * Throws an exception if the lock cannot be acquired.
     *
     * @throws LockException Lock could not be acquired.
     * @return void
     */
    protected function lock()
    {
        if (!$this->resource->lockMigrations($this->execModuleName)) {
            throw new LockException($this->execModuleName);
        }
    }

    /**
     * Unlock a module's migrations.
     */
    protected function unlock()
    {
        $this->resource->unlockMigrations($this->execModuleName);
    }
}