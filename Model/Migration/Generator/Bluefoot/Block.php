<?php

namespace SomethingDigital\Migration\Model\Migration\Generator\Bluefoot;

use SomethingDigital\Migration\Model\Migration\Generator\Bluefoot as BluefootGenerator;
use SomethingDigital\Migration\Model\Cms\BlockRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Escaper;

class Block implements GeneratorInterface
{
    protected $bluefootEntityGenerator;
    protected $blockRepo;
    protected $searchCriteriaBuilder;
    protected $escaper;

    public function __construct(
        Entity $bluefootEntityGenerator,
        BlockRepository $blockRepo,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Escaper $escaper
    ) {
        $this->bluefootEntityGenerator = $bluefootEntityGenerator;
        $this->blockRepo = $blockRepo;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->escaper = $escaper;
    }

    /**
     * Load all CMS blocks by identifier for all available stores
     *
     * @param string $identifier
     * @return \Magento\Cms\Api\Data\BlockSearchResultsInterface
     */
    public function getBlocksByIdentifier($identifier)
    {
        $this->searchCriteriaBuilder->addFilter('identifier', $identifier);
        $criteria = $this->searchCriteriaBuilder->create();
        return $this->blockRepo->getList($criteria);
    }

    /**
     * Generate code for full CMS block creation/updating
     *
     * Use existing CMS block by identifier as data source. Identifier can be obtained from $options
     *
     * @param \Magento\Framework\DataObject $options
     * @return string
     */
    public function makeCode($options)
    {
        $result = $this->getBlocksByIdentifier($options->getCmsEntityIdentifier());
        if ($result->getTotalCount() == 0) {
            throw new UsageException(__('Block %1 was not found', $options->getCmsEntityIdentifier()));
        }
        $code = '';
        foreach ($result->getItems() as $block) {
            $code .= $this->makeCommentCode($block, $options)
                . $this->makeBluefootEntitiesCode($block, $options)
                . $this->makeExtraDataCode($block)
                . $this->makeBlockCode($block, $options);
        }
        return $code;
    }

    /**
     * Generate comment for code
     *
     * @param \Magento\Cms\Api\Data\BlockInterface $block
     * @param \Magento\Framework\DataObject $options
     * @return string
     */
    protected function makeCommentCode($block, $options)
    {
        $code = '';
        if ($options->getMigrationOperation() == BluefootGenerator::OPERATION_CREATE) {
            $code = '
        // create CMS block "' . $block->getIdentifier() . '"';
        } elseif ($options->getMigrationOperation() == BluefootGenerator::OPERATION_UPDATE) {
            $code = '
        // update CMS block "' . $block->getIdentifier() . '"';
        }
        return $code;
    }

    /**
     * Generate code for bluefoot entities creation/updating
     *
     * @param \Magento\Cms\Api\Data\BlockInterface $block
     * @param \Magento\Framework\DataObject $options
     * @return string
     */
    protected function makeBluefootEntitiesCode($block, $options)
    {
        // make quotation for content here because $this->bluefootEntityGenerator->makeCode() returns content
        // concatenated with php-variables
        list($content, $bluefootEntitiesCode) = $this->bluefootEntityGenerator->makeCode($this->escaper->escapeJsQuote($block->getContent()), $options);
        // update block content to display it in generated code, do not save it
        $block->setContent($content);
        return $bluefootEntitiesCode;
    }

    /**
     * Generate code for $extraData param
     *
     * @param \Magento\Cms\Api\Data\BlockInterface $block
     * @return string
     */
    protected function makeExtraDataCode($block)
    {
        return '
        $extraData = [
            \'title\' => \'' . $this->escaper->escapeJsQuote($block->getTitle()) . '\',
            \'is_active\' => \'' . $block->getIsActive() . '\',
            \'stores\' => [' . implode(',', $block->getStores()) . ']
        ];
';
    }

    /**
     * Generate code for CMS block creation/updating
     *
     * @param \Magento\Cms\Api\Data\BlockInterface $block
     * @param \Magento\Framework\DataObject $options
     * @return string
     */
    protected function makeBlockCode($block, $options)
    {
        $code = '';
        if ($options->getMigrationOperation() == BluefootGenerator::OPERATION_CREATE) {
            $code = '
        $this->block->create(\'' . $block->getIdentifier() . '\', \'' . $this->escaper->escapeJsQuote($block->getTitle()) . '\', '
                . '\'' . $block->getContent() . '\', $extraData);
';
        } elseif ($options->getMigrationOperation() == BluefootGenerator::OPERATION_UPDATE) {
            $code = '
        $this->block->update(\'' . $block->getIdentifier() . '\', \'' . $block->getContent() . '\', $extraData);
';
        }
        return $code;
    }
}
