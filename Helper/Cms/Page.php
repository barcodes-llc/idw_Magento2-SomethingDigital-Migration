<?php

namespace SomethingDigital\Migration\Helper\Cms;

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory as PageFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use SomethingDigital\Migration\Exception\UsageException;

class Page
{
    protected $pageRepo;
    protected $pageFactory;
    protected $searchCriteriaBuilder;
    protected $storeManager;

    public function __construct(
        PageRepositoryInterface $pageRepo,
        PageFactory $pageFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager
    ) {
        $this->pageRepo = $pageRepo;
        $this->pageFactory = $pageFactory;
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

            $this->pageRepo->save($page);
        });
    }

    public function rename($identifier, $title, $storeId = Store::ADMIN_CODE)
    {
        $page = $this->find($identifier, $storeId);
        if ($page === null) {
            throw new UsageException(__('Page %s was not found', $identifier));
        }

        $this->withStore($storeId, function () use ($page, $title) {
            $page->setTitle($title);
            $this->pageRepo->save($page);
        });
    }

    public function update($identifier, $content, array $extra = [], $storeId = Store::ADMIN_CODE)
    {
        $page = $this->find($identifier, $storeId);
        if ($page === null) {
            throw new UsageException(__('Page %s was not found', $identifier));
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
            $this->pageRepo->save($page);
        });
    }

    public function delete($identifier, $storeId = Store::ADMIN_CODE, $requireExists = false)
    {
        $page = $this->find($identifier, $storeId);
        if ($page === null) {
            if ($requireExists) {
                throw new UsageException(__('Page %s was not found', $identifier));
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
            throw new UsageException('Found multiple matching pages.');
        }

        foreach ($results->getItems() as $page) {
            return $page;
        }

        return null;
    }
}
