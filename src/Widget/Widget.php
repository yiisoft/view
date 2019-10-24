<?php
declare(strict_types = 1);

namespace Yiisoft\Widget;

use ReflectionClass;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\View\ViewContextInterface;
use Yiisoft\View\WebView;
use Yiisoft\Widget\Event\AfterRun;
use Yiisoft\Widget\Event\BeforeRun;
use Yiisoft\Widget\Exception\InvalidConfigException;

/**
 * Widget is the base class for widgets.
 *
 * For more details and usage information on Widget, see the [guide article on widgets](guide:structure-widgets).
 */
class Widget
{
    /**
     * @var EventDispatcherInterface $eventDispatcher
     */
    private $eventDispatcher;

    /**
     * The widgets that are currently being rendered (not ended). This property is maintained by {@see begin()} and
     * {@see end} methods.
     *
     * @var Widget[] $stack
     */
    private $stack;

    public function __construct(WebView $webView)
    {
        $this->eventDispatcher = $webView->getEventDispatcher();
    }

    /**
     * Begin the rendering of content.
     *
     * @return Widget
     */
    public function begin(): Widget
    {
        $this->stack[] = $this;
        $this->init();

        return $this;
    }

    /**
     * Ends the rendering of content.
     *
     * @param string $class
     *
     * @return Widget
     */
    public function end(): Widget
    {
        if (!empty($this->stack)) {
            $widget = array_pop($this->stack);

            if (get_class($widget) === get_called_class()) {
                /* @var $widget Widget */
                if ($widget->beforeRun()) {
                    $result = $widget->run();
                    $result = $widget->afterRun($result);
                    echo $result;
                }
                return $widget;
            }
            throw new InvalidConfigException(
                'Expecting end() of ' . get_class($widget) . ', found ' . get_called_class()
            );
        }
        throw new InvalidConfigException(
            'Unexpected ' . get_called_class() . '::end() call. A matching begin() is not found.'
        );
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
     * The method will trigger the {@see AfterRun()} event. The return value of the method will be used as the widget
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
}
