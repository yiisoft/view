<?php

declare(strict_types=1);

namespace Yiisoft\Widget;

use BadFunctionCallException;
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
     * The widgets that are currently being rendered (not ended). This property is maintained by {@see static::begin()}
     * and {@see static::end()} methods.
     */
    protected static array $stack;
    protected static EventDispatcherInterface $eventDispatcher;
    protected static Widget $widget;
    protected static WebView $webView;

    public function __construct(EventDispatcherInterface $eventDispatcher, WebView $webView)
    {
        self::$eventDispatcher = $eventDispatcher;
        self::$webView = $webView;
    }

    /**
     * Begins a widget.
     *
     * This method creates an instance of the calling class. It will apply the configuration to the created instance.
     * A matching {@see end()} call should be called later. As some widgets may use output buffering, the {@see end()}
     * call should be made in the same view to avoid breaking the nesting of output buffers.
     *
     * @return static the newly created widget instance.
     *
     * {@see end()}
     */
    public static function begin(): self
    {
        $widget = new static(self::$eventDispatcher, self::$webView);

        self::$stack[] = $widget;

        return $widget;
    }

    /**
     * Ends a widget
     *
     * Note that the rendering result of the widget is directly echoed out
     *
     * @return static the widget instance that is ended
     *
     * @throws BadFunctionCallException if {@see begin()]} and {@see end()} calls are not properly nested.
     *
     * @see begin()
     */
    public static function end(): self
    {
        if (empty(self::$stack)) {
            throw new BadFunctionCallException(
                'Unexpected ' . static::class . '::end() call. A matching begin() is not found.'
            );
        }

        $widget = array_pop(self::$stack);
        if (get_class($widget) !== static::class) {
            throw new BadFunctionCallException('Expecting end() of ' . get_class($widget) . ', found ' . static::class);
        }
        if ($widget->beforeRun()) {
            $result = $widget->run();
            $result = $widget->afterRun($result);
            echo $result;
        }

        return $widget;
    }

    /**
     * Creates a widget instance.
     *
     * @return static $widget.
     */
    public static function widget(): self
    {
        $widget = new static(self::$eventDispatcher, self::$webView);

        static::$widget = $widget;

        return $widget;
    }

    /**
     * Returns the view object that can be used to render views or view files.
     *
     * The {@see render()} and {@see renderFile()} methods will use this view object to implement the actual view
     * rendering. If not set, it will default to the "view" application component.
     */
    public function getView(): WebView
    {
        return self::$webView;
    }

    public function init(): void
    {
    }

    public function getContent(): string
    {
        return '';
    }

    /**
     * Executes the widget.
     *
     * @return string the result of widget execution to be outputted.
     */
    public function run(): string
    {
        $out = '';
        $widget = static::$widget;

        if ($widget->beforeRun()) {
            $result = $widget->getContent();
            $out = $widget->afterRun($result);
        }

        return $out;
    }

    /**
     * Renders a view.
     * The view to be rendered can be specified in one of the following formats:
     * - [path alias](guide:concept-aliases) (e.g. "@app/views/site/index");
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
     * - absolute path within module (e.g. "/site/index"): the view name starts with a single slash.
     * - relative path (e.g. "index"): the actual view file will be looked for under {@see viewPath}.
     * If the view name does not contain a file extension, it will use the default one `.php`.
     *
     * @param string $view the view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws \Throwable
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
        $event = self::$eventDispatcher->dispatch($event);

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
     * @param string $result the widget return result.
     *
     * @return string the processed widget result.
     */
    public function afterRun(string $result): string
    {
        $event = new AfterRun($result);
        $event = self::$eventDispatcher->dispatch($event);

        return $event->getResult();
    }
}
