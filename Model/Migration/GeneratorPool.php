<?php

namespace SomethingDigital\Migration\Model\Migration;

use SomethingDigital\Migration\Model\Migration\GeneratorInterface;
use SomethingDigital\Migration\Model\Migration\GeneratorFactory;

class GeneratorPool
{
    /**
     * Factory
     *
     * @var GeneratorFactory
     */
    protected $factory;

    /**
     * Migration generators
     *
     * @var GeneratorInterface[]
     */
    protected $generators = [];

    /**
     * Constructor
     *
     * @param GeneratorFactory $factory
     */
    public function __construct(GeneratorFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Retrieve a migration generator instance by its unique name
     *
     * @param string $name
     * @return GeneratorInterface
     */
    public function get($name)
    {
        if (!isset($this->generators[$name])) {
            $this->generators[$name] = $this->factory->create($name);
        }
        return $this->generators[$name];
    }
}
