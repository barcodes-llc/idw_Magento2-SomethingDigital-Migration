<?php

namespace SomethingDigital\Migration\Helper\Cms;

use Gene\BlueFoot\Api\Data\EntityInterface;
use Gene\BlueFoot\Api\Data\EntityInterfaceFactory;
use Gene\BlueFoot\Api\EntityRepositoryInterface;
use SomethingDigital\Migration\Helper\AbstractHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use SomethingDigital\Migration\Exception\UsageException;

class Bluefoot extends AbstractHelper
{
    protected $bluefootEntityFactory;
    protected $bluefootEntityRepo;
    protected $searchCriteriaBuilder;

    public function __construct(
        EntityInterfaceFactory $bluefootEntityFactory,
        EntityRepositoryInterface $bluefootEntityRepo,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager
    ) {
        $this->bluefootEntityFactory = $bluefootEntityFactory;
        $this->bluefootEntityRepo = $bluefootEntityRepo;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
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
}
