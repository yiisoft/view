<?php

namespace Yiisoft\Widget;

use Psr\EventDispatcher\EventDispatcherInterface;
use yii\exceptions\InvalidConfigException;

/**
 * ContentDecorator records all output between [[begin()]] and [[end()]] calls, passes it to the given view file
 * as `$content` and then echoes rendering result.
 *
 * ```php
 * <?php ContentDecorator::begin([
 *     'viewFile' => '@app/views/layouts/base.php',
 *     'params' => [],
 *     'view' => $this,
 * ]) ?>
 *
 * some content here
 *
 * <?php ContentDecorator::end() ?>
 * ```
 *
 * There are [[\yii\base\View::beginContent()]] and [[\yii\base\View::endContent()]] wrapper methods in the
 * [[\yii\base\View]] component to make syntax more friendly. In the view these could be used as follows:
 *
 * ```php
 * <?php $this->beginContent('@app/views/layouts/base.php') ?>
 *
 * some content here
 *
 * <?php $this->endContent() ?>
 * ```
 */
class ContentDecorator extends Widget
{
    /**
     * @var string the view file that will be used to decorate the content enclosed by this widget.
     *             This can be specified as either the view file path or [path alias](guide:concept-aliases).
     */
    public $viewFile;
    /**
     * @var array the parameters (name => value) to be extracted and made available in the decorative view.
     */
    public $params = [];

    private function __construct(string $viewFile, EventDispatcherInterface $eventDispatcher)
    {
        $this->viewFile = $viewFile;
        parent::__construct($eventDispatcher);

        // Starts recording a clip.
        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * Ends recording a clip.
     * This method stops output buffering and saves the rendering result as a named clip in the controller.
     *
     * @return string the result of widget execution to be outputted.
     */
    public function run(): string
    {
        $params = $this->params;
        $params['content'] = ob_get_clean();
        // render under the existing context
        return $this->getView()->renderFile($this->viewFile, $params);
    }
}
