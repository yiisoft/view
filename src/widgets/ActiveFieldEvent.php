<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\widgets;

use yii\base\Event;

/**
 * ActiveFieldEvent represents the event parameter used for an active field event.
 *
 * @property ActiveForm $target the sender of this event.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 3.0.0
 */
class ActiveFieldEvent extends Event
{
    /**
     * @event raised right before rendering an ActiveField.
     * @since 3.0.0
     */
    const BEFORE_RENDER = 'beforeFieldRender';
    /**
     * @event raised right after rendering an ActiveField.
     * @since 3.0.0
     */
    const AFTER_RENDER = 'afterFieldRender';

    /**
     * @param string $name event name.
     * @param ActiveField $field the active field associated with this event.
     */
    public function __construct(string $name, ActiveField $field)
    {
        parent::__construct($name, $field);
    }

    /**
     * Creates BEFORE_RENDER event.
     * @param ActiveField $field the field being rendered.
     * @return self created event
     */
    public static function beforeRender(ActiveField $field): self
    {
        return new static(static::BEFORE_RENDER, $field);
    }

    /**
     * Creates BEFORE_RENDER event.
     * @param ActiveField $field the field being rendered.
     * @return self created event
     */
    public static function afterRender(ActiveField $field): self
    {
        return new static(static::AFTER_RENDER, $field);
    }

    /**
     * Get field the event is fired on.
     * @return ActiveField
     */
    public function getField(): ActiveField
    {
        return $this->getTarget();
    }
}
