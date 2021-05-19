<?php

declare(strict_types=1);

namespace Yiisoft\View;

use Psr\EventDispatcher\StoppableEventInterface;
use Yiisoft\View\Event\AfterRenderEventInterface;
use Yiisoft\View\Event\View\AfterRender;
use Yiisoft\View\Event\View\BeforeRender;
use Yiisoft\View\Event\View\PageBegin;
use Yiisoft\View\Event\View\PageEnd;

/**
 * View represents a view object in the MVC pattern.
 *
 * View provides a set of methods (e.g. {@see View::render()}) for rendering purpose.
 *
 * For more details and usage information on View, see the [guide article on views](guide:structure-views).
 */
final class View extends BaseView
{
    /**
     * Marks the beginning of a view.
     */
    public function beginPage(): void
    {
        ob_start();
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
