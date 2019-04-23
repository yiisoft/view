<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\widgets;

/**
 * ActiveFieldEvent represents the event parameter used for an active field event.
 *
 * @property ActiveForm $target the sender of this event.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 */
class ActiveFieldRenderEvent extends WidgetEvent
{
    /**
     * @event raised right before rendering an ActiveField.
     */
    const BEFORE = 'widget.form.field.render.before';
    /**
     * @event raised right after rendering an ActiveField.
     */
    const AFTER = 'widget.form.field.render.before';

    /**
     * @param string      $name  event name.
     * @param ActiveField $field the active field associated with this event.
     */
    public function __construct(string $name, ActiveField $field)
    {
        parent::__construct($name, $field);
    }

    /**
     * Creates BEFORE event.
     *
     * @param ActiveField $field the field being rendered.
     *
     * @return self created event
     */
    public static function before(ActiveField $field): self
    {
        return new static(static::BEFORE, $field);
    }

    /**
     * Creates BEFORE event.
     *
     * @param ActiveField $field the field being rendered.
     *
     * @return self created event
     */
    public static function after(ActiveField $field): self
    {
        return new static(static::AFTER, $field);
    }

    /**
     * Get field the event is fired on.
     *
     * @return ActiveField
     */
    public function getField(): ActiveField
    {
        return $this->getTarget();
    }
}
