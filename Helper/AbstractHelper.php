<?php

namespace SomethingDigital\Migration\Helper;

use Magento\Store\Model\StoreManagerInterface;

/**
 * Block helper
 *
 * Extra fields:
 *  - is_active: To set to.
 *  - store_id: To set to, and also for lookup on update.
 *  - custom_root_template: Design root template.
 */
abstract class AbstractHelper
{
    protected $storeManager;

    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Execute a function within a store's context.
     *
     * Returns the value the function returns.
     *
     * @param int|string $storeId Store id or code.
     * @param callable $func Function to execute.
     * @return mixed
     */
    protected function withStore($storeId, $func)
    {
        $currentStore = $this->storeManager->getStore()->getId();
        $this->storeManager->setCurrentStore($storeId);
        try {
            return $func();
        } finally {
            $this->storeManager->setCurrentStore($currentStore);
        }
    }
}
