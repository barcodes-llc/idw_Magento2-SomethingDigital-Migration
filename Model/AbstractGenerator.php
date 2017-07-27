<?php

namespace SomethingDigital\Migration\Model;

use Magento\Framework\Filesystem\Directory\Write as DirWrite;
use Magento\Framework\Filesystem\Directory\WriteFactory as DirWriteFactory;

abstract class AbstractGenerator
{
    protected $dirWriteFactory;

    public function __construct(DirWriteFactory $dirWriteFactory)
    {
        $this->dirWriteFactory = $dirWriteFactory;
    }

    protected function writeCode($filePath, $name, $code)
    {
        /** @var DirWrite $dirWrite */
        $dirWrite = $this->dirWriteFactory->create($filePath);
        $file = $dirWrite->openFile($name . '.php', 'w');
        $file->write($code);
        $file->close();
    }

    protected function logCode($filePath, $name, $code)
    {
        fprintf(STDERR, "Generating %s...\n%s\n\n", $name, $code);
    }
}
