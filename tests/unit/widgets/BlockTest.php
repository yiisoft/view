<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\tests\framework\widgets;

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

        $block = new Block($this->app);

        //fixme: is this event ok ??
        $block->on('init', function ($event) use (&$initTriggered) {
            $initTriggered = true;
            //$event->initTriggered = true;
        }, [$initTriggered]);

        ob_get_clean();

        $this->assertTrue($initTriggered);
    }
}
