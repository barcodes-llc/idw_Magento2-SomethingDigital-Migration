<?php

namespace SomethingDigital\Migration\Model\Migration\Generator\Bluefoot;

use SomethingDigital\Migration\Model\Migration\Generator\Bluefoot as BluefootGenerator;
use SomethingDigital\Migration\Model\Cms\PageRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Page implements GeneratorInterface
{
    protected $bluefootEntityGenerator;
    protected $pageRepo;
    protected $searchCriteriaBuilder;

    public function __construct(
        Entity $bluefootEntityGenerator,
        PageRepository $pageRepo,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->bluefootEntityGenerator = $bluefootEntityGenerator;
        $this->pageRepo = $pageRepo;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Load all CMS pages by identifier for all available stores
     *
     * @param string $identifier
     * @return \Magento\Cms\Api\Data\PageSearchResultsInterface
     */
    public function getPagesByIdentifier($identifier)
    {
        $this->searchCriteriaBuilder->addFilter('identifier', $identifier);
        $criteria = $this->searchCriteriaBuilder->create();
        return $this->pageRepo->getList($criteria);
    }

    /**
     * Generate code for full CMS page creation/updating
     *
     * Use existing CMS page by identifier as data source. Identifier can be obtained from $options
     *
     * @param \Magento\Framework\DataObject $options
     * @return string
     */
    public function makeCode($options)
    {
        $result = $this->getPagesByIdentifier($options->getCmsEntityIdentifier());
        if ($result->getTotalCount() == 0) {
            throw new UsageException(__('Page %1 was not found', $options->getCmsEntityIdentifier()));
        }
        $code = '';
        foreach ($result->getItems() as $page) {
            $code .= $this->makeCommentCode($page, $options)
                . $this->makeBluefootEntitiesCode($page, $options)
                . $this->makeExtraDataCode($page)
                . $this->makePageCode($page, $options);
        }
        return $code;
    }

    /**
     * Generate comment for code
     *
     * @param \Magento\Cms\Api\Data\PageInterface $page
     * @param \Magento\Framework\DataObject $options
     * @return string
     */
    protected function makeCommentCode($page, $options)
    {
        $code = '';
        if ($options->getMigrationOperation() == BluefootGenerator::OPERATION_CREATE) {
            $code = '
        // create CMS page "' . $page->getIdentifier() . '"';
        } elseif ($options->getMigrationOperation() == BluefootGenerator::OPERATION_UPDATE) {
            $code = '
        // update CMS page "' . $page->getIdentifier() . '"';
        }
        return $code;
    }

    /**
     * Generate code for bluefoot entities creation/updating
     *
     * @param \Magento\Cms\Api\Data\PageInterface $page
     * @param \Magento\Framework\DataObject $options
     * @return string
     */
    protected function makeBluefootEntitiesCode($page, $options)
    {
        list($content, $bluefootEntitiesCode) = $this->bluefootEntityGenerator->makeCode($page->getContent(), $options);
        // update page content to display it in generated code, do not save it
        $page->setContent($content);
        return $bluefootEntitiesCode;
    }

    /**
     * Generate code for $extraData param
     *
     * @param \Magento\Cms\Api\Data\PageInterface $page
     * @return string
     */
    protected function makeExtraDataCode($page)
    {
        return '
        $extraData = [
            \'title\' => \'' . $page->getTitle() . '\',
            \'page_layout\' => \'' . $page->getPageLayout() . '\',
            \'meta_title\' => \'' . $page->getMetaTitle() . '\',
            \'meta_keywords\' => \'' . $page->getMetaKeywords() . '\',
            \'meta_description\' => \'' . $page->getMetaDescription() . '\',
            \'content_heading\' => \'' . $page->getContentHeading() . '\',
            \'layout_update_xml\' => \'' . $page->getLayoutUpdateXml() . '\',
            \'custom_theme\' => \'' . $page->getCustomTheme() . '\',
            \'custom_root_template\' => \'' . $page->getCustomRootTemplate() . '\',
            \'is_active\' => \'' . $page->getIsActive() . '\',
            \'stores\' => [' . implode(',', $page->getStores()) . ']
        ];
';
    }

    /**
     * Generate code for CMS page creation/updating
     *
     * @param \Magento\Cms\Api\Data\PageInterface $page
     * @param \Magento\Framework\DataObject $options
     * @return string
     */
    protected function makePageCode($page, $options)
    {
        $code = '';
        if ($options->getMigrationOperation() == BluefootGenerator::OPERATION_CREATE) {
            $code = '
        $this->page->create(\'' . $page->getIdentifier() . '\', \'' . $page->getTitle() . '\', \'' . $page->getContent() . '\', $extraData);
';
        } elseif ($options->getMigrationOperation() == BluefootGenerator::OPERATION_UPDATE) {
            $code = '
        $this->page->update(\'' . $page->getIdentifier() . '\', \'' . $page->getContent() . '\', $extraData);
';
        }
        return $code;
    }
}
