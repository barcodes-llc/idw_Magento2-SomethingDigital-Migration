<?php

namespace SomethingDigital\Migration\Helper\Cms;

use Gene\BlueFoot\Api\Data\EntityInterface;
use Gene\BlueFoot\Api\Data\EntityInterfaceFactory;
use Gene\BlueFoot\Api\EntityRepositoryInterface;
use Gene\BlueFoot\Model\ResourceModel\Entity as BluefootEntityResource;
use SomethingDigital\Migration\Helper\AbstractHelper;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use SomethingDigital\Migration\Exception\UsageException;
use SomethingDigital\Migration\Model\Cms\BlockRepository;

class Bluefoot extends AbstractHelper
{
    protected $bluefootEntityFactory;
    protected $bluefootEntityRepo;
    protected $searchCriteriaBuilder;
    protected $attributeSetFactory;
    protected $bluefootEntityResource;
    protected $categoryList;
    protected $productRepo;
    protected $blockRepo;
    protected $state;

    public function __construct(
        EntityInterfaceFactory $bluefootEntityFactory,
        EntityRepositoryInterface $bluefootEntityRepo,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager,
        AttributeSetFactory $attributeSetFactory,
        BluefootEntityResource $bluefootEntityResource,
        CategoryListInterface $categoryList,
        ProductRepositoryInterface $productRepo,
        BlockRepository $blockRepo,
        State $state
    ) {
        $this->bluefootEntityFactory = $bluefootEntityFactory;
        $this->bluefootEntityRepo = $bluefootEntityRepo;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->bluefootEntityResource = $bluefootEntityResource;
        $this->categoryList = $categoryList;
        $this->productRepo = $productRepo;
        $this->blockRepo = $blockRepo;
        $this->state = $state;
        parent::__construct($storeManager);
    }

    /**
     * Create a new bluefoot entity.
     *
     * @param array $data
     */
    public function create($data)
    {
        /** @var EntityInterface $entity */
        $entity = $this->bluefootEntityFactory->create();
        $entity->setData($data);
        $this->bluefootEntityRepo->save($entity);
        return $entity->getId();
    }

    /**
     * Update a bluefoot entity's attributes
     *
     * @param int $id
     * @param array $data
     * @throws UsageException
     */
    public function update($id, $data)
    {
        $storeId = Store::ADMIN_CODE;
        $entity = $this->find($id);
        if ($entity === null) {
            throw new UsageException(__('Bluefoot entity %1 was not found', $id));
        }

        $this->withStore($storeId, function () use ($entity, $data) {
            foreach ($data as $key => $value) {
                $entity->setData($key, $value);
            }
            $this->bluefootEntityRepo->save($entity);
        });
    }

    /**
     * Delete bluefoot entity
     *
     * @param int $id
     * @throws UsageException
     */
    public function delete($id)
    {
        $entity = $this->find($id);
        if ($entity === null) {
            throw new UsageException(__('Bluefoot entity %1 was not found', $id));
        }
        $this->bluefootEntityRepo->delete($entity);
    }

    /**
     * Find a bluefoot entity for update or delete.
     *
     * @param int $id
     * @return EntityInterface
     */
    protected function find($id)
    {
        return $this->bluefootEntityRepo->getById($id);
    }

    public function findAttributeSetId($name)
    {
        $attributeSet = $this->attributeSetFactory->create()->load($name, 'attribute_set_name');
        if (!$attributeSet->getAttributeSetId()) {
            throw new UsageException(__('Could not find attribute set: %1', $name));
        }
        return $attributeSet->getAttributeSetId();
    }

    public function findAttributeOptionValue($code, $label)
    {
        $attribute = $this->bluefootEntityResource->getAttribute($code);
        if (!$attribute) {
            throw new UsageException(__('Attribute %1 not found', $code));
        }

        if (!$attribute->usesSource()) {
            throw new UsageException(__('Attribute %1 is not source-based, cannot lookup %2', $code, $label));
        }
        foreach ($attribute->getOptions() as $opt) {
            if ((string)$opt->getLabel() == $label) {
                return $opt->getValue();
            }
        }

        throw new UsageException(__('Attribute %1 had no option with label %2', $code, $label));
    }

    public function findProductId($sku)
    {
        try {
            return $this->state->emulateAreaCode('adminhtml', function ($sku) {
                $product = $this->productRepo->get($sku);
                return $product->getId();
            }, [$sku]);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    public function findCategoryId($urlPath)
    {
        $this->searchCriteriaBuilder->addFilter('url_path', $urlPath);
        $this->searchCriteriaBuilder->setPageSize(1)->setCurrentPage(1);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $categories = $this->categoryList->getList($searchCriteria)->getItems();
        // There should really only be one, but we return the first.
        foreach ($categories as $category) {
            return $category->getId();
        }

        // TODO: Fall back to base url_key if not found?
        return null;
    }

    public function findStaticBlockId($identifier)
    {
        $this->searchCriteriaBuilder->addFilter('identifier', $identifier);
        $this->searchCriteriaBuilder->setPageSize(1)->setCurrentPage(1);
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $blocks = $this->blockRepo->getList($searchCriteria)->getItems();
        // There should really only be one, but we return the first.
        foreach ($blocks as $block) {
            return $block->getId();
        }

        return null;
    }
}
