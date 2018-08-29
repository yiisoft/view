<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\widgets;

use yii\base\Event;

/**
 * WidgetEvent represents the event parameter used for a widget event.
 *
 * By setting the [[isValid]] property, one may control whether to continue running the widget.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 * @since 3.0
 */
class WidgetEvent extends Event
{
    /**
     * @event triggered when the widget is initialized via [[init()]].
     */
    public const INIT = 'widget.init';
}
