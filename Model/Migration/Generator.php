<?php

namespace SomethingDigital\Migration\Model\Migration;

use Magento\Framework\Filesystem\Directory\Write as DirWrite;
use Magento\Framework\Filesystem\Directory\WriteFactory as DirWriteFactory;
use SomethingDigital\Migration\Model\AbstractGenerator;

class Generator extends AbstractGenerator
{
    public function create($namespace, $filePath, $name)
    {
        $code = $this->makeCode($namespace, $name);
        $this->writeCode($filePath, $name, $code);
    }

    protected function makeCode($namespace, $name)
    {
        return '<?php

namespace ' . $namespace . ';

use Magento\Framework\Setup\SetupInterface;
use SomethingDigital\Migration\Api\MigrationInterface;
use SomethingDigital\Migration\Helper\Cms\Page as PageHelper;
use SomethingDigital\Migration\Helper\Cms\Block as BlockHelper;

class ' . $name . ' implements MigrationInterface
{
    protected $page;
    protected $block;

    public function __construct(PageHelper $page, BlockHelper $block)
    {
        $this->page = $page;
        $this->block = $block;
    }

    public function execute(SetupInterface $setup)
    {
        // TODO: $this->page->create(\'identifer\', \'Title\', \'<p>Content</p>\');
    }
}
';
    }
}