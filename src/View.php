<?php

declare(strict_types=1);

namespace Yiisoft\View;

use Yiisoft\View\Event\PageBegin;
use Yiisoft\View\Event\PageEnd;

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
     * Marks the beginning of a page.
     */
    public function beginPage(): void
    {
        ob_start();
        PHP_VERSION_ID >= 80000 ? ob_implicit_flush(false) : ob_implicit_flush(0);

        $this->eventDispatcher->dispatch(new PageBegin($this->getViewFile()));
    }

    /**
     * Marks the ending of a page.
     */
    public function endPage(): void
    {
        $this->eventDispatcher->dispatch(new PageEnd($this->getViewFile()));
        ob_end_flush();
    }
}
