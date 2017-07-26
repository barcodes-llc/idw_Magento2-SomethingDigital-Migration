<?php

namespace SomethingDigital\Migration\Model\Migration\Generator\Bluefoot;

use Gene\BlueFoot\Api\EntityRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\AttributeInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use SomethingDigital\Migration\Model\Migration\Generator\Bluefoot as BluefootGenerator;
use SomethingDigital\Migration\Model\Migration\Generator\Escaper;

class Entity
{
    protected $bluefootEntityRepo;
    protected $searchCriteriaBuilder;
    protected $escaper;
    protected $attributeSetFactory;

    public function __construct(
        EntityRepositoryInterface $bluefootEntityRepo,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Escaper $escaper,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->bluefootEntityRepo = $bluefootEntityRepo;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->escaper = $escaper;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * Generate code for bluefoot entities creation/updating
     *
     * Use existing CMS entity content as data source. This method also replace bluefoot entity IDs in content by variables.
     * Returns [$code, $content]
     *
     * @param string $content
     * @param \Magento\Framework\DataObject $options
     * @return array
     */
    public function makeCode($content, $options)
    {
        $bluefootEntities = $this->parseContent($content);
        // Always generate "create" code for bluefoot entities. Obviously the same cms block/page may have
        // different bluefoot entity IDs on different environments (dev, staging, production)
        return $this->makeCreateCode($bluefootEntities, $content);
    }

    /**
     * Retrieve list of bluefoot entity IDs from CMS entity content
     *
     * Return loaded bluefoot entities
     *
     * @param string $content
     * @return array
     */
    protected function parseContent($content)
    {
        if (!preg_match_all('/\"entityId\"\:\"(\d+?)\"/m', $content, $matches)) {
            return [];
        }
        if (!isset($matches[1])) {
            return [];
        }

        $this->searchCriteriaBuilder->addFilter('entity_id', $matches[1], 'in');
        $criteria = $this->searchCriteriaBuilder->create();
        $result = $this->bluefootEntityRepo->getList($criteria);
        if ($result->getTotalCount() == 0) {
            return [];
        }
        return $result->getItems();
    }

    /**
     * Generate code for creation of all bluefoot entities from CMS entity content
     *
     * @param array $bluefootEntities
     * @param string $content
     * @return array
     */
    protected function makeCreateCode($bluefootEntities, $content)
    {
        $code = '';
        foreach ($bluefootEntities as $bluefootEntity) {
            $data = $bluefootEntity->getData();
            unset($data['created_at']);
            unset($data['updated_at']);
            $code .= '
        $data' . $bluefootEntity->getId() . ' = [';
            foreach ($data as $key => $value) {
                if ($key == 'entity_id' || $value === null) {
                    continue;
                }
                $code .= '
            \'' . $key . '\' => ' . $this->makeValueCode($bluefootEntity, $key, $value) . ',';
            }
            $code .= '
        ];
        $bluefootEntity' . $bluefootEntity->getId() . ' = $this->bluefoot->create($data' . $bluefootEntity->getId() . ');
';
            $content = str_replace('"entityId":"' . $bluefootEntity->getId() . '"', '"entityId":"\' . $bluefootEntity' . $bluefootEntity->getId() . ' . \'"', $content);
        }
        return [$content, $code];
    }

    /**
     * Generate code for updating of all bluefoot entities from CMS entity content
     *
     * @param array $bluefootEntities
     * @param string $content
     * @return array
     */
    protected function makeUpdateCode($bluefootEntities, $content)
    {
        $code = '';
        foreach ($bluefootEntities as $bluefootEntity) {
            $data = $bluefootEntity->getData();
            unset($data['created_at']);
            unset($data['updated_at']);
            $code .= '
        $data' . $bluefootEntity->getId() . ' = [';
            foreach ($data as $key => $value) {
                if ($key == 'entity_id' || $value === null) {
                    continue;
                }
                $code .= '
            \'' . $key . '\' => ' . $this->makeValueCode($bluefootEntity, $key, $value) . ',';
            }
            $code .= '
            \'updated_at\' => gmdate(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT)
        ];
        $this->bluefoot->update(' . $bluefootEntity->getId() . ', $data' . $bluefootEntity->getId() . ');
';
        }
        return [$content, $code];
    }

    protected function makeValueCode($bluefootEntity, $key, $value)
    {
        if ($key === 'attribute_set_id') {
            return $this->makeAttributeSetValueCode($value);
        }

        /** @var \Gene\BlueFoot\Model\Attribute $attribute */
        $attribute = $bluefootEntity->getResource()->getAttribute($key);
        if ($attribute->usesSource()) {
            $map = $this->getAttributeOptionMap($attribute);
            if (isset($map[$value])) {
                return '$this->bluefoot->findAttributeOptionValue(' . $this->escaper->escapeQuote($key) . ', ' . $this->escaper->escapeQuote($map[$value]) . ')';
            }
        }

        return $this->escaper->escapeQuote($value);
    }

    protected function makeAttributeSetValueCode($value)
    {
        $attributeSet = $this->attributeSetFactory->create()->load($value);
        $name = $attributeSet->getAttributeSetName();

        return '$this->bluefoot->findAttributeSetId(' . $this->escaper->escapeQuote($name) . ')';
    }

    protected function getAttributeOptionMap(AttributeInterface $attribute)
    {
        $map = [];
        foreach ($attribute->getOptions() as $opt) {
            $map[$opt->getValue()] = (string)$opt->getLabel();
        }

        return $map;
    }
}
