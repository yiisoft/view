<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Event\WebView;

use PHPUnit\Framework\TestCase;
use Yiisoft\View\Event\WebView\WebViewEvent;
use Yiisoft\View\Tests\TestSupport\TestHelper;
use Yiisoft\View\WebView;

abstract class WebViewEventTestCase extends TestCase
{
    public function testBase(): void
    {
        $view = TestHelper::createWebView();

        $event = $this->createEvent($view);

        $this->assertSame($view, $event->getView());
    }

    abstract protected function createEvent(WebView $view): WebViewEvent;
}
