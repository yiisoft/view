<?php
declare(strict_types = 1);

namespace Yiisoft\Widget\Tests;

use Yiisoft\Tests\TestCase;
use Yiisoft\Widget\Tests\Stubs\TestWidget;
use Yiisoft\Widget\Tests\Stubs\TestWidgetA;
use Yiisoft\Widget\Tests\Stubs\TestWidgetB;
use Yiisoft\Widget\Widget;
use Yiisoft\Widget\Exception\InvalidConfigException;

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
        $testWidget = (new TestWidget($this->webView))
            ->id('w0')
            ->run();

        $this->assertSame('<run-w0>', $testWidget);
    }

    public function testBeginEnd(): void
    {
        ob_start();
        ob_implicit_flush(0);

        $testWidgetA = (new TestWidgetA($this->webView))
            ->id('test')
            ->begin();

        $this->assertInstanceOf(Widget::class, $testWidgetA);

        $testWidgetA->end();

        $output = ob_get_clean();

        $this->assertSame('<run-test>', $output);
    }

    public function testWidgetConstruc(): void
    {
        ob_start();
        ob_implicit_flush(0);

        $testWidgetB = (new TestWidgetB($this->webView, $this->logger))
            ->id('test')
            ->begin();

        $this->assertInstanceOf(Widget::class, $testWidgetB);

        $testWidgetB->end();

        $output = ob_get_clean();

        $this->assertSame('<run-test-construct>', $output);
    }

    /**
     * @depends testBeginEnd
     */
    public function testStackTracking(): void
    {
        $this->expectException(InvalidConfigException::class);
        $testWidgetA = new TestWidgetA($this->webView);
        $testWidgetA->end();
    }

    /**
     * @depends testBeginEnd
     */
    public function testStackTrackingDisorder(): void
    {
        $this->expectException(InvalidConfigException::class);
        $testWidgetA = new TestWidgetA($this->webView);
        $testWidgetB = new TestWidgetB($this->webView, $this->logger);
        $testWidgetA->begin();
        $testWidgetB->end();
    }
}
