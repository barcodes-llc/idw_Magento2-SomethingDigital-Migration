<?php

namespace SomethingDigital\Migration\Model\Migration\Generator\Bluefoot;

use Magento\Framework\Api\SearchCriteriaBuilder;
use SomethingDigital\Migration\Exception\UsageException;
use SomethingDigital\Migration\Model\Cms\PageRepository;
use SomethingDigital\Migration\Model\Migration\Generator\Bluefoot as BluefootGenerator;
use SomethingDigital\Migration\Model\Migration\Generator\Escaper;

class Page implements GeneratorInterface
{
    protected $bluefootEntityGenerator;
    protected $pageRepo;
    protected $searchCriteriaBuilder;
    protected $escaper;

    public function __construct(
        Entity $bluefootEntityGenerator,
        PageRepository $pageRepo,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Escaper $escaper
    ) {
        $this->bluefootEntityGenerator = $bluefootEntityGenerator;
        $this->pageRepo = $pageRepo;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->escaper = $escaper;
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
        // make quotation for content here because $this->bluefootEntityGenerator->makeCode() returns content
        // concatenated with php-variables
        list($content, $bluefootEntitiesCode) = $this->bluefootEntityGenerator->makeCode($this->escaper->escapeQuote($page->getContent()), $options);
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
            \'title\' => ' . $this->escaper->escapeQuote($page->getTitle()) . ',
            \'page_layout\' => ' . $this->escaper->escapeQuote($page->getPageLayout()) . ',
            \'meta_title\' => ' . $this->escaper->escapeQuote($page->getMetaTitle()) . ',
            \'meta_keywords\' => ' . $this->escaper->escapeQuote($page->getMetaKeywords()) . ',
            \'meta_description\' => ' . $this->escaper->escapeQuote($page->getMetaDescription()) . ',
            \'content_heading\' => ' . $this->escaper->escapeQuote($page->getContentHeading()) . ',
            \'layout_update_xml\' => ' . $this->escaper->escapeQuote($page->getLayoutUpdateXml()) . ',
            \'custom_theme\' => ' . $this->escaper->escapeQuote($page->getCustomTheme()) . ',
            \'custom_root_template\' => ' . $this->escaper->escapeQuote($page->getCustomRootTemplate()) . ',
            \'is_active\' => ' . (int) $page->getIsActive() . ',
            \'stores\' => [' . implode(', ', $page->getStores()) . ']
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
        // $page->getContent() contains already escaped string, it also wrapped with single quotes
        $code = '';
        if ($options->getMigrationOperation() == BluefootGenerator::OPERATION_CREATE) {
            $code = '
        $this->page->create(\'' . $page->getIdentifier() . '\', ' . $this->escaper->escapeQuote($page->getTitle()) . ', '
                . $page->getContent() . ', $extraData);
';
        } elseif ($options->getMigrationOperation() == BluefootGenerator::OPERATION_UPDATE) {
            $code = '
        $this->page->update(\'' . $page->getIdentifier() . '\', ' . $page->getContent() . ', $extraData);
';
        }
        return $code;
    }
}
