<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\view\events;

/**
 * RenderEvent represents events triggered by the [[View]] component.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 * @since 1.0
 */
class RenderEvent extends Event
{
    /**
     * @event triggered by [[View::renderFile()]] right before it renders a view file.
     */
    const BEFORE = 'view.render.before';
    /**
     * @event triggered by [[View::renderFile()]] right after it renders a view file.
     */
    const AFTER = 'view.render.after';

    /**
     * Creates BEFORE event.
     * @param string $viewFile the view file being rendered.
     * @param array $params array passed to the [[View::render()]] method.
     * @return self created event
     */
    public static function before(string $viewFile, array $params): self
    {
        return new static(static::BEFORE, $viewFile, $params);
    }

    /**
     * Creates AFTER event with result. 
     * @param string $viewFile the view file being rendered.
     * @param array $params array passed to the [[View::render()]] method.
     * @param mixed $result view rendering result.
     * @return self created event
     */
    public static function after(string $viewFile, array $params, $result): self
    {
        return (new static(static::AFTER, $viewFile, $params))->setResult($result);
    }
}
