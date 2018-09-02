<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\view;

/**
 * ViewNotFoundException represents an exception caused by view file not found.
 *
 * @author Alexander Makarov
 */
class ViewNotFoundException extends \BadMethodCallException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'View not Found';
    }
}
