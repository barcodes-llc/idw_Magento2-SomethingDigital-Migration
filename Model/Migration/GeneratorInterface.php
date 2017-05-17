<?php

namespace SomethingDigital\Migration\Model\Migration;

interface GeneratorInterface
{
    /**
     * Generate code and put it into php file
     *
     * @param string $namespace
     * @param string $filePath
     * @param string $name
     * @param array $options
     */
    public function create($namespace, $filePath, $name, $options = []);
}
