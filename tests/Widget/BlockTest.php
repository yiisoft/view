<?php
declare(strict_types = 1);

namespace Yiisoft\Widget\Tests;

use Yiisoft\Tests\TestCase;
use Yiisoft\Widget\Block;
use Yiisoft\Widget\Event\BeforeRun;

/**
 * @group widgets
 */
class BlockTest extends TestCase
{
    public function testBlock(): void
    {
        $block = (new Block($this->webView))
            ->id('testme')
            ->begin();

        echo '<block-testme>';

        $block->end();

        $this->assertStringContainsString('<block-testme>', $this->webView->getBlock('testme'));
    }

    public function testBlockRenderInPlaceTrue(): void
    {
        ob_start();
        ob_implicit_flush(0);

        $block = (new Block($this->webView))
            ->id('testme')
            ->renderInPlace(true)
            ->begin();

        echo '<block-testme>';

        $block->end();

        $this->assertStringContainsString('<block-testme>', ob_get_clean());
    }

    public function testGetBlockException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->webView->getBlock('notfound');
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/15536
     */
    public function testShouldTriggerInitEvent(): void
    {
        $initTriggered = false;

        // adding some listeners
        $this->listenerProvider->attach(function (BeforeRun $event) use (&$initTriggered) {
            $initTriggered = true;
        });

        ob_start();
        ob_implicit_flush(0);

        $block = (new Block($this->webView))->begin();
        $block->end();

        ob_get_clean();

        $this->assertTrue($initTriggered);
    }
}
