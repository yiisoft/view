<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Event\WebView;

use PHPUnit\Framework\TestCase;
use Yiisoft\View\Event\WebView\WebViewEvent;
use Yiisoft\View\Tests\TestSupport\TestHelper;
use Yiisoft\View\WebView;

abstract class WebViewEventTest extends TestCase
{
    public function testBase(): void
    {
        $view = TestHelper::createWebView();
        $parameters = ['planet' => 'Earth'];

        $event = $this->createEvent($view, $parameters);

        $this->assertSame($view, $event->getView());
        $this->assertSame($parameters, $event->getParameters());
    }

    abstract protected function createEvent(WebView $view, array $parameters): WebViewEvent;
}
