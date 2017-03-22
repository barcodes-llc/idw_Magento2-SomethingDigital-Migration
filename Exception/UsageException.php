<?php

namespace SomethingDigital\Migration\Exception;

use Magento\Framework\Exception\LocalizedException;

class UsageException extends LocalizedException
{
    public function __construct($error)
    {
        parent::__construct($error);
    }
}
