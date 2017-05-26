<?php

namespace SomethingDigital\Migration\Console\Input;

use Symfony\Component\Console\Input\InputInterface;
use Magento\Framework\DataObject;

interface ParserInterface
{
    /**
     * Parse CLI input data
     *
     * Return data object with parsed migration options. Other parsers add or override options.
     *
     * @param InputInterface $input
     * @return \Magento\Framework\DataObject
     */
    public function parse(InputInterface $input, DataObject $result);
}
