<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\view;

/**
 * ViewEvent represents events triggered by the [[View]] component.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 1.0
 */
abstract class ViewEvent extends \yii\events\Event
{
    /**
     * @var string the view file being rendered.
     */
    public $viewFile;
    /**
     * @var array the parameter array passed to the [[View::render()]] method.
     */
    public $params;

    /**
     * @param string $name event name
     */
    public function __construct(string $name, string $viewFile, array $params = [])
    {
        parent::__construct($name);
        $this->viewFile = $viewFile;
        $this->params = $params;
    }
}
