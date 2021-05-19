<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Event\WebView;

use Yiisoft\View\Event\WebView\PageBegin;
use Yiisoft\View\Event\WebView\WebViewEvent;
use Yiisoft\View\WebView;

final class PageBeginTest extends WebViewEventTest
{
    protected function createEvent(WebView $view): WebViewEvent
    {
        return new PageBegin($view);
    }
}
