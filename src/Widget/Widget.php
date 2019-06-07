<?php
namespace Yiisoft\Widget;

use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionClass;
use yii\exceptions\InvalidCallException;
use yii\helpers\Yii;
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
     * @var int a counter used to generate [[id]] for widgets.
     *
     * @internal
     */
    public static $counter = 0;
    /**
     * @var string the prefix to the automatically generated widget IDs.
     *
     * @see getId()
     */
    public static $autoIdPrefix = 'w';
    /**
     * @var Widget[] the widgets that are currently being rendered (not ended). This property
     *               is maintained by [[begin()]] and [[end()]] methods.
     *
     * @internal
     */
    public static $stack = [];

    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Begins a widget.
     * This method creates an instance of the calling class. It will apply the configuration
     * to the created instance. A matching [[end()]] call should be called later.
     * As some widgets may use output buffering, the [[end()]] call should be made in the same view
     * to avoid breaking the nesting of output buffers.
     *
     * @param array $config name-value pairs that will be used to initialize the object properties
     *
     * @return static the newly created widget instance
     *
     * @see end()
     */
    public static function begin($config = [])
    {
        $config['__class'] = static::class;
        /* @var $widget Widget */
        $widget = Yii::createObject($config);
        self::$stack[] = $widget;

        return $widget;
    }

    /**
     * Ends a widget.
     * Note that the rendering result of the widget is directly echoed out.
     *
     * @return static the widget instance that is ended.
     *
     * @throws InvalidCallException if [[begin()]] and [[end()]] calls are not properly nested
     *
     * @see begin()
     */
    public static function end()
    {
        if (!empty(self::$stack)) {
            $widget = array_pop(self::$stack);
            if (get_class($widget) === static::class) {
                /* @var $widget Widget */
                if ($widget->beforeRun()) {
                    $result = $widget->run();
                    $result = $widget->afterRun($result);
                    echo $result;
                }

                return $widget;
            }

            throw new InvalidCallException('Expecting end() of ' . get_class($widget) . ', found ' . static::class);
        }

        throw new InvalidCallException('Unexpected ' . static::class . '::end() call. A matching begin() is not found.');
    }

    /**
     * Creates a widget instance and runs it.
     * The widget rendering result is returned by this method.
     *
     * @param array $config name-value pairs that will be used to initialize the object properties
     *
     * @return string the rendering result of the widget.
     * @throws \Exception
     *
     */
    public static function widget($config = []): string
    {
        ob_start();
        ob_implicit_flush(false);

        try {
            /* @var $widget Widget */
            $config['__class'] = static::class;
            $widget = Yii::createObject($config);
            $out = '';
            if ($widget->beforeRun()) {
                $result = $widget->run();
                $out = $widget->afterRun($result);
            }
        } catch (\Exception $e) {
            // close the output buffer opened above if it has not been closed already
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw $e;
        }

        return ob_get_clean() . $out;
    }

    private $id;

    /**
     * Returns the ID of the widget.
     *
     * @param bool $autoGenerate whether to generate an ID if it is not set previously
     *
     * @return string ID of the widget.
     */
    public function getId($autoGenerate = true): string
    {
        if ($autoGenerate && $this->id === null) {
            $this->id = static::$autoIdPrefix . static::$counter++;
        }

        return $this->id;
    }

    /**
     * Sets the ID of the widget.
     *
     * @param string $value id of the widget.
     */
    public function setId(string $value): void
    {
        $this->id = $value;
    }

    private $view;

    /**
     * Returns the view object that can be used to render views or view files.
     * The [[render()]] and [[renderFile()]] methods will use
     * this view object to implement the actual view rendering.
     * If not set, it will default to the "view" application component.
     */
    public function getView(): WebView
    {
        return $this->view;
    }

    /**
     * Sets the view object to be used by this widget.
     *
     * @param WebView $view the view object that can be used to render views or view files.
     */
    public function setView(WebView $view): void
    {
        $this->view = $view;
    }

    /**
     * Executes the widget.
     *
     * @return string the result of widget execution to be outputted.
     */
    public function run(): string
    {
    }

    /**
     * Renders a view.
     *
     * The view to be rendered can be specified in one of the following formats:
     *
     * - [path alias](guide:concept-aliases) (e.g. "@app/views/site/index");
     * - absolute path within application (e.g. "//site/index"): the view name starts with double slashes.
     *   The actual view file will be looked for under the [[Application::viewPath|view path]] of the application.
     * - absolute path within module (e.g. "/site/index"): the view name starts with a single slash.
     *   The actual view file will be looked for under the [[Module::viewPath|view path]] of the currently
     *   active module.
     * - relative path (e.g. "index"): the actual view file will be looked for under [[viewPath]].
     *
     * If the view name does not contain a file extension, it will use the default one `.php`.
     *
     * @param string $view the view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     *
     * @return string the rendering result.
     * @throws InvalidArgumentException if the view file does not exist.
     *
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
     * @throws InvalidArgumentException if the view file does not exist.
     *
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
     */
    public function getViewPath(): string
    {
        $class = new ReflectionClass($this);

        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'views';
    }

    /**
     * This method is invoked right before the widget is executed.
     *
     * The method will trigger the [[BeforeRun]] event. The return value of the method
     * will determine whether the widget should continue to run.
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
        $this->eventDispatcher->dispatch($event);

        return !$event->isPropagationStopped();
    }

    /**
     * This method is invoked right after a widget is executed.
     *
     * The method will trigger the [[AfterRun]] event. The return value of the method
     * will be used as the widget return value.
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
        $this->eventDispatcher->dispatch($event);

        return $event->getResult();
    }
}
