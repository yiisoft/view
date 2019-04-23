<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\view;

/**
 * PageEvent represents events triggered when rendering HTML page.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class PageEvent extends ViewEvent
{
    /**
     * @event triggered by [[yii\view\View::beginPage()]].
     */
    const BEGIN = 'view.page.begin';
    /**
     * @event triggered by [[yii\view\View::endPage()]].
     */
    const END = 'view.page.end';
}
