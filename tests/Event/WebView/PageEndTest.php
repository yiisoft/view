<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Event\WebView;

use Yiisoft\View\Event\WebView\PageEnd;
use Yiisoft\View\Event\WebView\WebViewEvent;
use Yiisoft\View\WebView;

final class PageEndTest extends WebViewEventTestCase
{
    protected function createEvent(WebView $view): WebViewEvent
    {
        return new PageEnd($view);
    }
}
