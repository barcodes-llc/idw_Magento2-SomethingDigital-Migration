<?php

namespace SomethingDigital\Migration\Console\Input;

use Magento\Framework\ObjectManagerInterface;
use SomethingDigital\Migration\Console\Input\ParserInterface;

class ParserFactory
{
    /**
     * Object manager
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * CLI Input parsers
     *
     * @var array
     */
    protected $parsers;

    /**
     * Constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param array $parsers Format: array('<name>' => 'Parser\Class', ...)
     */
    public function __construct(ObjectManagerInterface $objectManager, array $parsers)
    {
        $this->objectManager = $objectManager;
        $this->parsers = $parsers;
    }

    /**
     * Retrieve a input CLI parser instance by its unique name
     *
     * @param string $name
     * @return ParserInterface
     */
    public function create($name)
    {
        if (!isset($this->parsers[$name])) {
            throw new \InvalidArgumentException("Unknown CLI input parser type: '{$name}'.");
        }
        $parserClass = $this->parsers[$name];
        $parserInstance = $this->objectManager->create($parserClass);
        if (!$parserInstance instanceof \SomethingDigital\Migration\Console\Input\ParserInterface) {
            throw new \UnexpectedValueException("{$parserInstance} has to implement the ParserInterface.");
        }
        return $parserInstance;
    }

    /**
     * Create array of all parser instances
     *
     * @return array
     */
    public function getList()
    {
        $parserInstances = [];
        foreach ($this->parsers as $name => $class) {
            $parserInstances[] = $this->create($name);
        }
        return $parserInstances;
    }
}
