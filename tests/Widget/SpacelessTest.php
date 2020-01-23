<?php

declare(strict_types=1);

namespace Yiisoft\Widget\Tests;

use Yiisoft\Tests\TestCase;
use Yiisoft\Widget\Spaceless;
use Yiisoft\Widget\Event\BeforeRun;

/**
 * SpacelessTest.
 */
class SpacelessTest extends TestCase
{
    public function testWidget(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo "<body>\n";

        Spaceless::begin()->init();
        echo "\t<div class='wrapper'>\n";

        Spaceless::begin()->init();
        echo "\t\t<div class='left-column'>\n";
        echo "\t\t\t<p>This is a left bar!</p>\n";
        echo "\t\t</div>\n\n";
        echo "\t\t<div class='right-column'>\n";
        echo "\t\t\t<p>This is a right bar!</p>\n";
        echo "\t\t</div>\n";
        Spaceless::end();

        echo "\t</div>\n";
        Spaceless::end();

        echo "\t<p>Bye!</p>\n";
        echo "</body>\n";

        $expected = "<body>\n<div class='wrapper'><div class='left-column'><p>This is a left bar!</p>".
            "</div><div class='right-column'><p>This is a right bar!</p></div></div>\t<p>Bye!</p>\n</body>\n";
        $this->assertEquals($expected, ob_get_clean());
    }

    public function testShouldTriggerBeforeRun(): void
    {
        $initTriggered = false;

        // adding some listeners
        $this->listenerProvider->attach(static function (BeforeRun $event) use (&$initTriggered) {
            $initTriggered = true;
        });

        Spaceless::begin()->init();
        Spaceless::end();

        $this->assertTrue($initTriggered);
    }
}
