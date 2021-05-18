<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Event\WebView;

use Yiisoft\View\Event\WebView\PageEnd;
use Yiisoft\View\Event\WebView\WebViewEvent;
use Yiisoft\View\WebView;

final class PageEndTest extends WebViewEventTest
{
    protected function createEvent(WebView $view, array $parameters): WebViewEvent
    {
        return new PageEnd($view, $parameters);
    }
}
