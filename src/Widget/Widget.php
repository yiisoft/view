<?php
declare(strict_types = 1);

namespace Yiisoft\Widget;

use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionClass;
use Yiisoft\View\ViewContextInterface;
use Yiisoft\View\WebView;
use Yiisoft\Widget\Event\AfterRun;
use Yiisoft\Widget\Event\BeforeRun;

/**
 * Widget is the base class for widgets.
 *
 * For more details and usage information on Widget, see the [guide article on widgets](guide:structure-widgets).
 */
class Widget implements ViewContextInterface
{
    /**
     * @var EventDispatcherInterface event handler.
     */
    protected $eventDispatcher;

    /**
     * @var WebView $view
     */
    protected $webView;

    public function __construct(EventDispatcherInterface $eventDispatcher, WebView $webView)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->webView = $webView;
    }

    public static function begin(WebView $view): self
    {
        return $view->beginWidget(static::class);
    }

    public static function end(WebView $view): self
    {
        return $view->endWidget(static::class);
    }

    public static function widget(WebView $view): self
    {
        return $view->widget(static::class);
    }

    /**
     * Returns the view object that can be used to render views or view files.
     *
     * The {@see render()} and {@see renderFile()} methods will use this view object to implement the actual view
     * rendering. If not set, it will default to the "view" application component.
     */
    public function getView(): WebView
    {
        return $this->webView;
    }

    public function init(): void
    {
    }

    public function getContent(): string
    {
    }

    /**
     * Executes the widget.
     *
     * @return string the result of widget execution to be outputted.
     */
    public function run(): string
    {
        $out = '';

        if ($this->beforeRun()) {
            $result = $this->getContent();
            $out = $this->afterRun($result);
        }

        return $out;
    }

    /**
     * Renders a view.
     *
     * The view to be rendered can be specified in one of the following formats:
     *
     * - [path alias](guide:concept-aliases) (e.g. "@app/views/site/index");
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
     * - absolute path within module (e.g. "/site/index"): the view name starts with a single slash.
     * - relative path (e.g. "index"): the actual view file will be looked for under {@see viewPath}.
     *
     * If the view name does not contain a file extension, it will use the default one `.php`.
     *
     * @param string $view the view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     *
     * @return string the rendering result.
     */
    public function render(string $view, array $params = []): string
    {
        return $this->getView()->render($view, $params, $this);
    }

    /**
     * Renders a view file.
     *
     * @param string $file the view file to be rendered. This can be either a file path or a [path alias](guide:concept-aliases).
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     *
     * @return string the rendering result.
     * @throws \Throwable
     */
    public function renderFile(string $file, array $params = []): string
    {
        return $this->getView()->renderFile($file, $params, $this);
    }

    /**
     * Returns the directory containing the view files for this widget.
     * The default implementation returns the 'views' subdirectory under the directory containing the widget class file.
     *
     * @return string the directory containing the view files for this widget.
     *
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     */
    public function getViewPath(): string
    {
        $class = new ReflectionClass($this);

        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'views';
    }

    /**
     * This method is invoked right before the widget is executed.
     *
     * The method will trigger the {@see BeforeRun()} event. The return value of the method will determine whether the
     * widget should continue to run.
     *
     * When overriding this method, make sure you call the parent implementation like the following:
     *
     * ```php
     * public function beforeRun()
     * {
     *     if (!parent::beforeRun()) {
     *         return false;
     *     }
     *
     *     // your custom code here
     *
     *     return true; // or false to not run the widget
     * }
     * ```
     *
     * @return bool whether the widget should continue to be executed.
     */
    public function beforeRun(): bool
    {
        $event = new BeforeRun();
        $event = $this->eventDispatcher->dispatch($event);

        return !$event->isPropagationStopped();
    }

    /**
     * This method is invoked right after a widget is executed.
     *
     * The method will trigger the {@see {AfterRun()} event. The return value of the method will be used as the widget
     * return value.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function afterRun($result)
     * {
     *     $result = parent::afterRun($result);
     *     // your custom code here
     *     return $result;
     * }
     * ```
     *
     * @param mixed $result the widget return result.
     *
     * @return mixed the processed widget result.
     */
    public function afterRun($result)
    {
        $event = new AfterRun($result);
        $event = $this->eventDispatcher->dispatch($event);

        return $event->getResult();
    }

    public function __toString()
    {
        return $this->run();
    }
}
