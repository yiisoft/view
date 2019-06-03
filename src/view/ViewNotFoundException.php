<?php
namespace Yiisoft\View;

/**
 * ViewNotFoundException represents an exception caused by view file not found.
 */
class ViewNotFoundException extends \BadMethodCallException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'View not Found';
    }
}
