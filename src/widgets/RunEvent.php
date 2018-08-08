<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\view\widgets;

/**
 * RunEvent represents events triggered while [[Widget::run()]].
 *
 * By setting the [[isValid]] property, one may control whether to continue running the widget.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 * @since 3.0.0
 */
class RunEvent extends WidgetEvent
{
    /**
     * @event raised right before executing a widget.
     * You may set [[isValid]] to be false to cancel the widget execution.
     */
    const BEFORE = 'widget.run.before';
    /**
     * @event raised right after executing a widget.
     */
    const AFTER = 'widget.run.after';

    /**
     * Creates BEFORE event.
     * @return self created event
     */
    public static function before(): self
    {
        return new static(static::BEFORE);
    }

    /**
     * Creates AFTER event with result.
     * @param mixed $result widget return result.
     * @return self created event
     */
    public static function after($result): self
    {
        return (new static(static::AFTER))->setResult($result);
    }
}
