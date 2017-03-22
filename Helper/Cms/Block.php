<?php

namespace SomethingDigital\Migration\Helper\Cms;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory as BlockFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use SomethingDigital\Migration\Exception\UsageException;

class Block
{
    protected $blockRepo;
    protected $blockFactory;
    protected $searchCriteriaBuilder;
    protected $storeManager;

    public function __construct(
        BlockRepositoryInterface $blockRepo,
        BlockFactory $blockFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager
    ) {
        $this->blockRepo = $blockRepo;
        $this->blockFactory = $blockFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
    }

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

    public function replace($identifier, $title, $content = '', array $extra = [])
    {
        $this->delete($identifier, false);
        $this->create($identifier, $title, $content, $extra);
    }

    public function create($identifier, $title, $content = '', array $extra = [])
    {
        // BlockRepository sets the current store id.
        $storeId = isset($extra['store_id']) ? $extra['store_id'] : Store::ADMIN_CODE;
        $this->withStore($storeId, function () use ($identifier, $title, $content, $extra) {
            /** @var BlockInterface $block */
            $block = $this->blockFactory->create();
            $block->setIdentifier($identifier);
            $block->setTitle($title);
            $block->setContent($content);
            $block->setIsActive(isset($extra['is_active']) ? $extra['is_active'] : true);

            $this->blockRepo->save($block);
        });
    }

    public function rename($identifier, $title, $storeId = Store::ADMIN_CODE)
    {
        $block = $this->find($identifier, $storeId);
        if ($block === null) {
            throw new UsageException(__('Block %s was not found', $identifier));
        }

        $this->withStore($storeId, function () use ($block, $title) {
            $block->setTitle($title);
            $this->blockRepo->save($block);
        });
    }

    public function update($identifier, $content, array $extra = [], $storeId = Store::ADMIN_CODE)
    {
        $block = $this->find($identifier, $storeId);
        if ($block === null) {
            throw new UsageException(__('Block %s was not found', $identifier));
        }

        $this->withStore($storeId, function () use ($block, $content, $extra) {
            if ($content !== null) {
                $block->setContent($content);
            }
            if (isset($extra['is_active'])) {
                $block->setIsActive($extra['is_active']);
            }
            $this->blockRepo->save($block);
        });
    }

    public function delete($identifier, $storeId = Store::ADMIN_CODE, $requireExists = false)
    {
        $block = $this->find($identifier, $storeId);
        if ($block === null) {
            if ($requireExists) {
                throw new UsageException(__('Block %s was not found', $identifier));
            }
            return;
        }

        $this->withStore($storeId, function () use ($block) {
            $this->blockRepo->delete($block);
        });
    }

    /**
     * Find a block for update or delete.
     *
     * @param string $identifier Block text identifier.
     * @param int|string $storeId Store id.
     * @throws UsageException Multiple blocks found.
     * @return BlockInterface|null
     */
    protected function find($identifier, $storeId = Store::ADMIN_CODE)
    {
        $this->searchCriteriaBuilder->addFilter('identifier', $identifier);
        $this->searchCriteriaBuilder->addFilter('store_id', $storeId);
        $criteria = $this->searchCriteriaBuilder->create();
        $results = $this->blockRepo->getList($criteria);

        $count = $results->getTotalCount();
        if ($count == 0) {
            return null;
        } elseif ($count > 1) {
            throw new UsageException('Found multiple matching blocks.');
        }

        foreach ($results->getItems() as $block) {
            return $block;
        }

        return null;
    }
}
