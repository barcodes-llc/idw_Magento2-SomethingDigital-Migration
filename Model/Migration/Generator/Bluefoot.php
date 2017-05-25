<?php

namespace SomethingDigital\Migration\Model\Migration\Generator;

use SomethingDigital\Migration\Model\Migration\GeneratorInterface;
use SomethingDigital\Migration\Model\AbstractGenerator;
use Magento\Framework\Filesystem\Directory\WriteFactory as DirWriteFactory;
use SomethingDigital\Migration\Model\Migration\Generator\Bluefoot\PageFactory;
use SomethingDigital\Migration\Model\Migration\Generator\Bluefoot\BlockFactory;

class Bluefoot extends AbstractGenerator implements GeneratorInterface
{
    const NAME = 'bluefoot';
    const CMS_ENTITY_PAGE = 'page';
    const CMS_ENTITY_BLOCK = 'block';
    const OPERATION_CREATE = 'create';
    const OPERATION_UPDATE = 'update';

    protected $pageGeneratorFactory;
    protected $blockGeneratorFactory;

    public function __construct(DirWriteFactory $dirWriteFactory, PageFactory $pageGeneratorFactory, BlockFactory $blockGeneratorFactory)
    {
        $this->pageGeneratorFactory = $pageGeneratorFactory;
        $this->blockGeneratorFactory = $blockGeneratorFactory;
        parent::__construct($dirWriteFactory);
    }

    public function create($namespace, $filePath, $name, \Magento\Framework\DataObject $options)
    {
        $code = $this->makeCode($namespace, $name, $options);
        $this->writeCode($filePath, $name, $code);
    }

    /**
     * Instantiate CMS entity generator object (block or page)
     *
     * @param \Magento\Framework\DataObject $options
     * @return \SomethingDigital\Migration\Model\Migration\Generator\Bluefoot\GeneratorInterface
     * @throws \InvalidArgumentException
     */
    protected function getCmsEntityGenerator($options)
    {
        $cmsEntityGenerator = null;
        switch ($options->getCmsEntityType()) {
            case static::CMS_ENTITY_PAGE:
                $cmsEntityGenerator = $this->pageGeneratorFactory->create();
                break;
            case static::CMS_ENTITY_BLOCK:
                $cmsEntityGenerator = $this->blockGeneratorFactory->create();
                break;
            default:
                throw new \InvalidArgumentException("Unknown migration generator cms entity type: '{$options->getCmsEntityType()}'. Could be 'block' or 'page'.");
                break;
        }
        return $cmsEntityGenerator;
    }

    /**
     * Generate full migration class code
     *
     * @param string $namespace
     * @param string $name
     * @param \Magento\Framework\DataObject $options
     * @return string
     */
    protected function makeCode($namespace, $name, $options)
    {
        $code = $this->getCmsEntityGenerator($options)->makeCode($options);
        return '<?php

namespace ' . $namespace . ';

use Magento\Framework\Setup\SetupInterface;
use SomethingDigital\Migration\Api\MigrationInterface;
use SomethingDigital\Migration\Helper\Cms\Page as PageHelper;
use SomethingDigital\Migration\Helper\Cms\Block as BlockHelper;
use SomethingDigital\Migration\Helper\Cms\Bluefoot as BluefootHelper;
use SomethingDigital\Migration\Helper\Email\Template as EmailHelper;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;

class ' . $name . ' implements MigrationInterface
{
    protected $page;
    protected $block;
    protected $bluefoot;
    protected $email;
    protected $resourceConfig;

    public function __construct(PageHelper $page, BlockHelper $block, BluefootHelper $bluefoot, EmailHelper $email, ResourceConfig $resourceConfig)
    {
        $this->page = $page;
        $this->block = $block;
        $this->bluefoot = $bluefoot;
        $this->email = $email;
        $this->resourceConfig = $resourceConfig;
    }

    public function execute(SetupInterface $setup)
    {
        ' . $code . '
    }
}
';
    }
}
