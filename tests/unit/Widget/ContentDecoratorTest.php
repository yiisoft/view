<?php
namespace Yiisoft\View\Tests\Widget;

use yii\widgets\ContentDecorator;

/**
 * @group widgets
 */
class ContentDecoratorTest extends \yii\tests\TestCase
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

        $contentDecorator = $this->factory->create([
            '__class'        => ContentDecorator::class,
            'viewFile'       => '@app/views/layouts/base.php',
            'on widget.init' => function () use (&$initTriggered) {
                $initTriggered = true;
            },
        ]);

        ob_get_clean();

        $this->assertTrue($initTriggered);
    }
}
