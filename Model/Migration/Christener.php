<?php

namespace SomethingDigital\Migration\Model\Migration;

use Magento\Framework\Stdlib\DateTime\DateTime;
use UnexpectedValueException;

class Christener
{
    // Used in database fields.
    const MAX_LENGTH = 64;

    protected $date;

    public function __construct(DateTime $date)
    {
        $this->date = $date;
    }

    public function christen($nickname)
    {
        // This just applies rules to the name to make it safe for use.
        $name = $this->cleanupName($nickname);
        if ($name == '') {
            throw new UnexpectedValueException('Invalid name: must use letters and numbers.');
        }

        $date = $this->date->gmtDate('YmdHis');
        // Must start with a letter.
        $fullName = 'M' . $date . $name;

        $this->validate($fullName);
        return $fullName;
    }

    protected function cleanupName($nickname)
    {
        // Uppercase anything after a separator.
        $nickname = preg_replace_callback('~[_ -][a-z]~', function($m) {
            return strtoupper($m[0]);
        }, $nickname);

        // And capitalize the first letter too.
        $nickname = ucfirst($nickname);

        // We can't even have underscores - it won't load the class correctly.
        return preg_replace('~[^a-zA-Z0-9\x7F-\xFF]~', '', $nickname);
    }

    protected function validate($name)
    {
        if (strlen($name) > 64) {
            throw new UnexpectedValueException('Invalid name: too long, be more succinct.');
        }
    }
}
