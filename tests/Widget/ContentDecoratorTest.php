<?php

declare(strict_types=1);

namespace Yiisoft\Widget\Tests;

use Yiisoft\Tests\TestCase;
use Yiisoft\Widget\ContentDecorator;
use Yiisoft\Widget\Event\BeforeRun;

/**
 * ContentDecoratorTest.
 */
class ContentDecoratorTest extends TestCase
{
    /**
     * @see https://github.com/yiisoft/yii2/issues/15536
     */
    public function testShouldTriggerInitEvent(): void
    {
        $initTriggered = false;

        // adding some listeners
        $this->listenerProvider->attach(static function (BeforeRun $event) use (&$initTriggered) {
            $initTriggered = true;
        });

        ob_start();
        ob_implicit_flush(0);

        ContentDecorator::begin()
            ->viewFile($this->aliases->get('@view/layout.php'))
            ->params([])
            ->init();

        echo "\t\t<div class='left-column'>\n";
        echo "\t\t\t<p>This is a left bar!</p>\n";
        echo "\t\t</div>\n\n";
        echo "\t\t<div class='right-column'>\n";
        echo "\t\t\t<p>This is a right bar!</p>\n";
        echo "\t\t</div>\n";

        ContentDecorator::end();

        $this->assertTrue($initTriggered);

        $expected = "\t\t<div class='left-column'>\n" .
                    "\t\t\t<p>This is a left bar!</p>\n" .
                    "\t\t</div>\n\n" .
                    "\t\t<div class='right-column'>\n" .
                    "\t\t\t<p>This is a right bar!</p>\n" .
                    "\t\t</div>\n";

        $this->assertStringContainsString($expected, ob_get_clean());
    }
}
