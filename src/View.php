<?php

declare(strict_types=1);

namespace Yiisoft\View;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Yiisoft\View\Event\AfterRenderEventInterface;
use Yiisoft\View\Event\View\AfterRender;
use Yiisoft\View\Event\View\BeforeRender;
use Yiisoft\View\Event\View\PageBegin;
use Yiisoft\View\Event\View\PageEnd;

use Yiisoft\View\State\ViewState;

use function ob_end_flush;
use function ob_implicit_flush;
use function ob_start;

/**
 * View represents an instance of a view for use in an any environment.
 *
 * View provides a set of methods (e.g. {@see View::render()}) for rendering purpose.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @property ViewState $state
 */
final class View implements ViewInterface
{
    use ViewTrait;

    private ViewState $state;

    /**
     * @param string $basePath The full path to the base directory of views.
     * @param ViewState $state
     * @param EventDispatcherInterface $eventDispatcher The event dispatcher instance.
     */
    public function __construct(string $basePath, ViewState $state, EventDispatcherInterface $eventDispatcher)
    {
        $this->basePath = $basePath;
        $this->state = $state;
        $this->eventDispatcher = $eventDispatcher;
        $this->setPlaceholderSalt(__DIR__);
    }

    /**
     * Marks the beginning of a view.
     */
    public function beginPage(): void
    {
        ob_start();
        /** @psalm-suppress InvalidArgument */
        PHP_VERSION_ID >= 80000 ? ob_implicit_flush(false) : ob_implicit_flush(0);

        $this->eventDispatcher->dispatch(new PageBegin($this));
    }

    /**
     * Marks the ending of a view.
     */
    public function endPage(): void
    {
        $this->eventDispatcher->dispatch(new PageEnd($this));

        ob_end_flush();
    }

    protected function createBeforeRenderEvent(string $viewFile, array $parameters): StoppableEventInterface
    {
        return new BeforeRender($this, $viewFile, $parameters);
    }

    protected function createAfterRenderEvent(
        string $viewFile,
        array $parameters,
        string $result
    ): AfterRenderEventInterface {
        return new AfterRender($this, $viewFile, $parameters, $result);
    }
}
