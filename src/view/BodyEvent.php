<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\view;

/**
 * BodyEvent represents events triggered when rendering HTML page body.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 * @since 1.0
 */
class BodyEvent extends ViewEvent
{
    /**
     * @event triggered by [[yii\web\View::beginBody()]].
     */
    const BEGIN = 'view.body.begin';
    /**
     * @event triggered by [[yii\web\View::endBody()]].
     */
    const END = 'view.body.end';
}
