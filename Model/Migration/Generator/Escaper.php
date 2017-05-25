<?php

namespace SomethingDigital\Migration\Model\Migration\Generator;

class Escaper
{
    /**
     * Escape quotes and add slashes to prepare string to be pasted in php code
     *
     * Returning string is already wrapped up into single quotes
     *
     * @param string $string
     * @return string
     */
    public function escapeQuote($string)
    {
        return var_export($string, true);
    }
}
