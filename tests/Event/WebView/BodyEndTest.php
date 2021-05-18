<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Event\WebView;

use Yiisoft\View\Event\WebView\BodyEnd;
use Yiisoft\View\Event\WebView\WebViewEvent;
use Yiisoft\View\WebView;

final class BodyEndTest extends WebViewEventTest
{
    protected function createEvent(WebView $view, array $parameters): WebViewEvent
    {
        return new BodyEnd($view, $parameters);
    }
}
