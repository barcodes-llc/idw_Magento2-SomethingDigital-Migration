<?php

namespace SomethingDigital\Migration\Model\Migration\Generator;

use Magento\Framework\DataObject;
use SomethingDigital\Migration\Model\Migration\GeneratorInterface;
use SomethingDigital\Migration\Model\AbstractGenerator;

class Standard extends AbstractGenerator implements GeneratorInterface
{
    const NAME = 'standard';

    public function create($namespace, $filePath, $name, DataObject $options)
    {
        $code = $this->makeCode($namespace, $name);
        if ($options->getDry()) {
            $this->logCode($filePath, $name, $code);
        } else {
            $this->writeCode($filePath, $name, $code);
        }
    }

    protected function makeCode($namespace, $name)
    {
        return '<?php

namespace ' . $namespace . ';

use Magento\Framework\Setup\SetupInterface;
use SomethingDigital\Migration\Api\MigrationInterface;
use SomethingDigital\Migration\Helper\Cms\Page as PageHelper;
use SomethingDigital\Migration\Helper\Cms\Block as BlockHelper;
use SomethingDigital\Migration\Helper\Email\Template as EmailHelper;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;

class ' . $name . ' implements MigrationInterface
{
    protected $page;
    protected $block;
    protected $email;
    protected $resourceConfig;

    public function __construct(PageHelper $page, BlockHelper $block, EmailHelper $email, ResourceConfig $resourceConfig)
    {
        $this->page = $page;
        $this->block = $block;
        $this->email = $email;
        $this->resourceConfig = $resourceConfig;
    }

    public function execute(SetupInterface $setup)
    {
        // TODO: $this->page->create(\'identifer\', \'Title\', \'<p>Content</p>\');
    }
}
';
    }
}
