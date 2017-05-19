<?php

namespace SomethingDigital\Migration\Console\Input\Parser;

use SomethingDigital\Migration\Console\Input\ParserInterface;
use SomethingDigital\Migration\Model\Migration\Generator\Bluefoot as GeneratorBluefoot;
use Symfony\Component\Console\Input\InputInterface;
use Magento\Framework\DataObject;

class Bluefoot implements ParserInterface
{
    /**
     * Parse CLI input data
     *
     * Parse options related to interactions with bluefoot cms content
     *
     * @param InputInterface $input
     * @return \Magento\Framework\DataObject
     */
    public function parse(InputInterface $input, DataObject $result)
    {
        if ($blockIdentifier = $input->getOption('update-from-block')) {
            $result->setGenerator(GeneratorBluefoot::NAME);
            $result->setMigrationOperation(GeneratorBluefoot::OPERATION_UPDATE);
            $result->setCmsEntityType(GeneratorBluefoot::CMS_ENTITY_BLOCK);
            $result->setCmsEntityIdentifier($blockIdentifier);
        } elseif ($blockIdentifier = $input->getOption('create-from-block')) {
            $result->setGenerator(GeneratorBluefoot::NAME);
            $result->setMigrationOperation(GeneratorBluefoot::OPERATION_CREATE);
            $result->setCmsEntityType(GeneratorBluefoot::CMS_ENTITY_BLOCK);
            $result->setCmsEntityIdentifier($blockIdentifier);
        } elseif ($pageIdentifier = $input->getOption('update-from-page')) {
            $result->setGenerator(GeneratorBluefoot::NAME);
            $result->setMigrationOperation(GeneratorBluefoot::OPERATION_UPDATE);
            $result->setCmsEntityType(GeneratorBluefoot::CMS_ENTITY_PAGE);
            $result->setCmsEntityIdentifier($pageIdentifier);
        } elseif ($pageIdentifier = $input->getOption('create-from-page')) {
            $result->setGenerator(GeneratorBluefoot::NAME);
            $result->setMigrationOperation(GeneratorBluefoot::OPERATION_CREATE);
            $result->setCmsEntityType(GeneratorBluefoot::CMS_ENTITY_PAGE);
            $result->setCmsEntityIdentifier($pageIdentifier);
        }
        return $result;
    }
}
