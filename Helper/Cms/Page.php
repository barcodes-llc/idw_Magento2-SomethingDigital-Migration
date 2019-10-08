<?php

namespace SomethingDigital\Migration\Helper\Cms;

use SomethingDigital\Migration\Model\Cms\PageRepository;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory as PageFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use SomethingDigital\Migration\Exception\UsageException;
use SomethingDigital\Migration\Helper\AbstractHelper;

/**
 * Page helper
 *
 * Extra fields:
 *  - is_active: To set to.
 *  - store_id: To set to, and also for lookup on update.
 *  - custom_root_template: Design root template.
 */
class Page extends AbstractHelper
{
    protected $pageRepo;
    protected $pageFactory;
    protected $searchCriteriaBuilder;

    public function __construct(
        PageRepository $pageRepo,
        PageFactory $pageFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($storeManager);

        $this->pageRepo = $pageRepo;
        $this->pageFactory = $pageFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * DELETE the original page and create a new one.
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
     * Create a new page.
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
        // PageRepository sets the current store id.
        $storeId = isset($extra['store_id']) ? $extra['store_id'] : Store::ADMIN_CODE;
        $this->withStore($storeId, function () use ($identifier, $title, $content, $extra) {
            /** @var PageInterface $page */
            $page = $this->pageFactory->create();
            $page->setIdentifier($identifier);
            $page->setTitle($title);
            $page->setContent($content);
            $page->setIsActive(isset($extra['is_active']) ? $extra['is_active'] : true);
            if (isset($extra['custom_root_template'])) {
                $page->setCustomRootTemplate($extra['custom_root_template']);
            }
            if (isset($extra['page_layout'])) {
                $page->setPageLayout($extra['page_layout']);
            }

            $this->pageRepo->save($page);
        });
    }

    /**
     * Rename a page's title.
     *
     * @param string $identifier Identifier code.
     * @param string $title Title to set.
     * @param int|string $storeId Store id or code to find the page.
     * @throws UsageException Page not found for update.
     */
    public function rename($identifier, $title, $storeId = Store::ADMIN_CODE)
    {
        $page = $this->find($identifier, $storeId);
        if ($page === null) {
            throw new UsageException(__('Page %1 was not found', $identifier));
        }

        $this->withStore($storeId, function () use ($page, $title) {
            $page->setTitle($title);
            $this->pageRepo->save($page);
        });
    }

    /**
     * Update a page's content or fields.
     *
     * @param string $identifier Identifier code.
     * @param string|null $content Updated content, or null to skip update.
     * @param mixed[] $extra Extra fields to set, and store_id for lookup.
     * @throws UsageException Page not found for update.
     */
    public function update($identifier, $content, array $extra = [])
    {
        $storeId = isset($extra['store_id']) ? $extra['store_id'] : Store::ADMIN_CODE;
        $page = $this->find($identifier, $storeId);
        if ($page === null) {
            throw new UsageException(__('Page %1 was not found', $identifier));
        }

        $this->withStore($storeId, function () use ($page, $content, $extra) {
            if ($content !== null) {
                $page->setContent($content);
            }
            if (isset($extra['is_active'])) {
                $page->setIsActive($extra['is_active']);
            }
            if (isset($extra['custom_root_template'])) {
                $page->setCustomRootTemplate($extra['custom_root_template']);
            }
            if (isset($extra['title'])) {
                $page->setTitle($extra['title']);
            }
            if (isset($extra['content_heading'])) {
                $page->setContentHeading($extra['content_heading']);
            }
            if (isset($extra['page_layout'])) {
                $page->setPageLayout($extra['page_layout']);
            }
            $this->pageRepo->save($page);
        });
    }

    /**
     * Delete a page.
     *
     * @param string $identifier Identifier code.
     * @param int|string $storeId Store id or code to find the page.
     * @param bool $requireExists Whether to fail if it doesn't exist.
     * @throws UsageException Page not found for delete.
     */
    public function delete($identifier, $storeId = Store::ADMIN_CODE, $requireExists = false)
    {
        $page = $this->find($identifier, $storeId);
        if ($page === null) {
            if ($requireExists) {
                throw new UsageException(__('Page %1 was not found', $identifier));
            }
            return;
        }

        $this->withStore($storeId, function () use ($page) {
            $this->pageRepo->delete($page);
        });
    }

    /**
     * Find a page for update or delete.
     *
     * @param string $identifier Page text identifier.
     * @param int|string $storeId Store id.
     * @throws UsageException Multiple pages found.
     * @return PageInterface|null
     */
    protected function find($identifier, $storeId = Store::ADMIN_CODE)
    {
        $this->searchCriteriaBuilder->addFilter('identifier', $identifier);
        $this->searchCriteriaBuilder->addFilter('store_id', $storeId);
        $criteria = $this->searchCriteriaBuilder->create();
        $results = $this->pageRepo->getList($criteria);

        $count = $results->getTotalCount();
        if ($count == 0) {
            return null;
        } elseif ($count > 1) {
            throw new UsageException(__('Found multiple matching pages.'));
        }

        foreach ($results->getItems() as $page) {
            return $page;
        }

        return null;
    }
}
