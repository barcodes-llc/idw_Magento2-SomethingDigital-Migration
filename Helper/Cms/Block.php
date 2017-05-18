<?php

namespace SomethingDigital\Migration\Helper\Cms;

use SomethingDigital\Migration\Model\Cms\BlockRepository;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory as BlockFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use SomethingDigital\Migration\Exception\UsageException;
use SomethingDigital\Migration\Helper\AbstractHelper;

/**
 * Block helper
 *
 * Extra fields:
 *  - is_active: To set to.
 *  - store_id: To set to, and also for lookup on update.
 */
class Block extends AbstractHelper
{
    protected $blockRepo;
    protected $blockFactory;
    protected $searchCriteriaBuilder;

    public function __construct(
        BlockRepository $blockRepo,
        BlockFactory $blockFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($storeManager);

        $this->blockRepo = $blockRepo;
        $this->blockFactory = $blockFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * DELETE the original block and create a new one.
     *
     * Used to reset settings.  Consider using update() instead.
     *
     * See class definition for extra fields.
     *
     * @param string $identifier Identifier code.
     * @param string $title Title to set.
     * @param string $content Contents to set.
     * @param mixed[] $extra Extra fields to set.
     */
    public function replace($identifier, $title, $content = '', array $extra = [])
    {
        $storeId = isset($extra['store_id']) ? $extra['store_id'] : Store::ADMIN_CODE;
        $this->delete($identifier, $storeId, false);
        $this->create($identifier, $title, $content, $extra);
    }

    /**
     * Create a new block.
     *
     * See class definition for extra fields.
     *
     * @param string $identifier Identifier code.
     * @param string $title Title to set.
     * @param string $content Contents to set.
     * @param mixed[] $extra Extra fields to set.
     */
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

    /**
     * Rename a block's title.
     *
     * @param string $identifier Identifier code.
     * @param string $title Title to set.
     * @param int|string $storeId Store id or code to find the block.
     * @throws UsageException Block not found for update.
     */
    public function rename($identifier, $title, $storeId = Store::ADMIN_CODE)
    {
        $block = $this->find($identifier, $storeId);
        if ($block === null) {
            throw new UsageException(__('Block %1 was not found', $identifier));
        }

        $this->withStore($storeId, function () use ($block, $title) {
            $block->setTitle($title);
            $this->blockRepo->save($block);
        });
    }

    /**
     * Update a block's content or fields.
     *
     * @param string $identifier Identifier code.
     * @param string|null $content Updated content, or null to skip update.
     * @param mixed[] $extra Extra fields to set, and store_id for lookup.
     * @throws UsageException Block not found for update.
     */
    public function update($identifier, $content, array $extra = [])
    {
        $storeId = isset($extra['store_id']) ? $extra['store_id'] : Store::ADMIN_CODE;
        $block = $this->find($identifier, $storeId);
        if ($block === null) {
            throw new UsageException(__('Block %1 was not found', $identifier));
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

    /**
     * Delete a block.
     *
     * @param string $identifier Identifier code.
     * @param int|string $storeId Store id or code to find the block.
     * @param bool $requireExists Whether to fail if it doesn't exist.
     * @throws UsageException Block not found for delete.
     */
    public function delete($identifier, $storeId = Store::ADMIN_CODE, $requireExists = false)
    {
        $block = $this->find($identifier, $storeId);
        if ($block === null) {
            if ($requireExists) {
                throw new UsageException(__('Block %1 was not found', $identifier));
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
            throw new UsageException(__('Found multiple matching blocks.'));
        }

        foreach ($results->getItems() as $block) {
            return $block;
        }

        return null;
    }
}
