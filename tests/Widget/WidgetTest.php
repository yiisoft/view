<?php
declare(strict_types=1);

namespace Yiisoft\Widget\Tests;

use Yiisoft\Tests\TestCase;
use Yiisoft\Widget\Tests\Stubs\TestWidget;
use Yiisoft\Widget\Tests\Stubs\TestWidgetA;
use Yiisoft\Widget\Tests\Stubs\TestWidgetB;
use Yiisoft\Widget\Widget;

/**
 * WidgetTest.
 */
class WidgetTest extends TestCase
{
    /**
     * @var Widget $widget
     */
    protected $widget;

    public function testWidget(): void
    {
        $output = TestWidget::widget($this->webView)->id('w0')->run();

        $this->assertSame('<run-w0>', $output);
    }

    public function testBeginEnd(): void
    {
        ob_start();
        ob_implicit_flush(0);

        $widget = TestWidgetA::begin($this->webView)->id('test');

        $this->assertInstanceOf(Widget::class, $widget);

        TestWidgetA::end($this->webView);
        $output = ob_get_clean();

        $this->assertSame('<run-test>', $output);
    }

    /**
     * @depends testBeginEnd
     */
    public function testStackTracking(): void
    {
        $this->expectException('BadFunctionCallException');
        TestWidget::end($this->webView);
    }

    /**
     * @depends testBeginEnd
     */
    public function testStackTrackingDisorder(): void
    {
        $this->expectException('BadFunctionCallException');

        TestWidgetA::begin($this->webView)->id('test');
        TestWidgetB::end($this->webView);
    }
}
