<?php

namespace SomethingDigital\Migration\Helper\Category;

use SomethingDigital\Migration\Helper\AbstractHelper;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;

class Category extends AbstractHelper
{
    const NAME = 'name';
    const CHILDREN = 'children';

    protected $storeManager;
    protected $websiteRepository;
    protected $groupRepository;
    protected $categoryRepository;
    protected $categoryFactory;
    protected $rootCategory;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepostiory
     * @param \Magento\Store\Api\GroupRepositoryInterface $groupRepostiory
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Catalog\Api\Data\CategoryInterfaceFactory $categoryFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        WebsiteRepositoryInterface $websiteRepository,
        GroupRepositoryInterface $groupRepository,
        CategoryRepositoryInterface $categoryRepository,
        CategoryInterfaceFactory $categoryFactory
    ) {
        $this->storeManager = $storeManager;
        $this->websiteRepository = $websiteRepository;
        $this->groupRepository = $groupRepository;
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * @return \Magento\Catalog\Api\Data\CategoryInterface
     */
    public function getRootCategory()
    {
        if (!$this->rootCategory) {
            $rootCategoryId = $this->groupRepository->get($this->websiteRepository->getDefault()->getDefaultGroupId())->getRootCategoryId();
            $this->rootCategory = $this->categoryRepository->get($rootCategoryId, Store::ADMIN_CODE);
        }
        return $this->rootCategory;
    }

    /**
     * @param string $name
     * @param \Magento\Catalog\Api\Data\CategoryInterface $parentCategory
     * @param array $children
     * @param array $extra
     */
    public function create($name, $parentCategory, $children = [], $extra = [])
    {
        try {
            $newCategory = $this->categoryFactory->create();
            $newCategory->setName($name);
            // add '/' to the end of path is workaround
            // Magento should add it automatically in resource _beforeSave(), but it stays unchanged due to some magic
            $newCategory->setPath($parentCategory->getPath() . '/');
            $newCategory->setParentId($parentCategory->getId());
            $newCategory->setIsActive(isset($extra['is_active']) ? $extra['is_active'] : true);
            $newCategory = $this->categoryRepository->save($newCategory);
            // update category path to be sure it is always correct
            // add this code for case when workaround above will be not necessary
            $newCategory->setPath(str_replace('//', '/', $newCategory->getPath()));
            $newCategory = $this->categoryRepository->save($newCategory);
            foreach ($children as $childCategory) {
                $this->createCategory(
                    $childCategory[SELF::NAME],
                    $newCategory,
                    isset($childCategory[SELF::CHILDREN]) ? $childCategory[SELF::CHILDREN] : []
                );
            }
        } catch (\Exception $e) {
            echo 'Can\'t create category with name "' . $name . '"' . PHP_EOL;
        }
    }
}
