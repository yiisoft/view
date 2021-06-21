<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Event\WebView;

use PHPUnit\Framework\TestCase;
use Yiisoft\View\Event\WebView\BeforeRender;
use Yiisoft\View\Tests\TestSupport\TestHelper;

final class BeforeRenderTest extends TestCase
{
    public function testBase(): void
    {
        $view = TestHelper::createWebView();
        $file = '/test.php';
        $parameters = ['planet' => 'Earth'];

        $event = new BeforeRender($view, $file, $parameters);
        $event->stopPropagation();

        $this->assertSame($view, $event->getView());
        $this->assertSame($file, $event->getFile());
        $this->assertSame($parameters, $event->getParameters());
        $this->assertTrue($event->isPropagationStopped());
    }
}
