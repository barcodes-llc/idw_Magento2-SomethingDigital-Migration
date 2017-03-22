<?php

namespace SomethingDigital\Migration\Exception;

use Magento\Framework\Exception\LocalizedException;

class LockException extends LocalizedException
{
    public function __construct($name)
    {
        parent::__construct(__('Database update in progress, please try again later.  Note: If persistent connections are enabled, flush all connections.'));
    }
}
