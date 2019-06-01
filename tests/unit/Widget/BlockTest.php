<?php

namespace Yiisoft\View\Tests\Widget;

use yii\widgets\Block;

/**
 * @group widgets
 */
class BlockTest extends \yii\tests\TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->mockWebApplication();
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/15536
     */
    public function testShouldTriggerInitEvent()
    {
        $initTriggered = false;

        $block = $this->factory->create([
            '__class'        => Block::class,
            'on widget.init' => function () use (&$initTriggered) {
                $initTriggered = true;
            },
        ]);

        ob_get_clean();

        $this->assertTrue($initTriggered);
    }
}
