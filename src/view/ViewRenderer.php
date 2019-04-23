<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\view;

/**
 * ViewRenderer is the base class for view renderer classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 */
abstract class ViewRenderer extends \yii\base\Component
{
    /**
     * Renders a view file.
     *
     * This method is invoked by [[View]] whenever it tries to render a view.
     * Child classes must implement this method to render the given view file.
     *
     * @param View   $view   the view object used for rendering the file.
     * @param string $file   the view file.
     * @param array  $params the parameters to be passed to the view file.
     *
     * @return string the rendering result
     */
    abstract public function render($view, $file, $params);
}
