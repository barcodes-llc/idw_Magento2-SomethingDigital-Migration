<?php

namespace SomethingDigital\Migration\Console\Input;

use SomethingDigital\Migration\Console\Input\ParserFactory;
use Symfony\Component\Console\Input\InputInterface;
use Magento\Framework\DataObject;

class ParserPool
{
    /**
     * Factory
     *
     * @var ParserFactory
     */
    protected $factory;

    /**
     * Constructor
     *
     * @param ParserFactory $factory
     */
    public function __construct(ParserFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Parse CLI input data
     *
     * Return data object with parsed migration options
     *
     * @param InputInterface $input
     * @return \Magento\Framework\DataObject
     */
    public function parse(InputInterface $input)
    {
        $result = new DataObject();
        foreach ($this->factory->getList() as $parser) {
            $parser->parse($input, $result);
        }
        return $result;
    }
}
