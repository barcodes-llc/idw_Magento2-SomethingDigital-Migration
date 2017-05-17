<?php

namespace SomethingDigital\Migration\Model\Migration;

use SomethingDigital\Migration\Model\Migration\GeneratorInterface;
use Magento\Framework\ObjectManagerInterface;

class GeneratorFactory
{
    /**
     * Object manager
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Migration generators
     *
     * @var array
     */
    protected $generators;

    /**
     * Constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param array $generators Format: array('<name>' => 'Generator\Class', ...)
     */
    public function __construct(ObjectManagerInterface $objectManager, array $generators)
    {
        $this->objectManager = $objectManager;
        $this->generators = $generators;
    }

    /**
     * Retrieve a migration generator instance by its unique name
     *
     * @param string $name
     * @return GeneratorInterface
     */
    public function create($name)
    {
        if (!isset($this->generators[$name])) {
            throw new \InvalidArgumentException("Unknown migration generator type: '{$name}'.");
        }
        $generatorClass = $this->generators[$name];
        $generatorInstance = $this->objectManager->create($generatorClass);
        if (!$generatorInstance instanceof GeneratorInterface) {
            throw new \UnexpectedValueException("{$generatorClass} has to implement the migration generator interface.");
        }
        return $generatorInstance;
    }
}
