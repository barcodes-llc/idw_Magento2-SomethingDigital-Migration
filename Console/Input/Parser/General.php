<?php

namespace SomethingDigital\Migration\Console\Input\Parser;

use SomethingDigital\Migration\Console\Input\ParserInterface;
use SomethingDigital\Migration\Model\Migration\Generator\Standard as GeneratorStandard;
use Symfony\Component\Console\Input\InputInterface;
use Magento\Framework\DataObject;

class General implements ParserInterface
{
    /**
     * Parse CLI input data
     *
     * Parse general migration options
     *
     * @param InputInterface $input
     * @return \Magento\Framework\DataObject
     */
    public function parse(InputInterface $input, DataObject $result)
    {
        $result->setModule($input->getOption('module'));
        $result->setType($input->getOption('type'));
        $result->setName($input->getArgument('name'));
        $result->setDry($input->getOption('dry'));
        $result->setGenerator(GeneratorStandard::NAME);
        return $result;
    }
}
