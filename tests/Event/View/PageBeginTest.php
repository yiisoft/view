<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Event\View;

use Yiisoft\View\Event\View\PageBegin;
use Yiisoft\View\Event\View\ViewEvent;
use Yiisoft\View\View;

final class PageBeginTest extends ViewEventTestCase
{
    protected function createEvent(View $view): ViewEvent
    {
        return new PageBegin($view);
    }
}
