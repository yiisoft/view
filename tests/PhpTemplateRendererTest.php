<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use LogicException;
use phpseclib3\Crypt\DH\Parameters;
use PHPUnit\Framework\TestCase;
use Yiisoft\View\PhpTemplateRenderer;
use Yiisoft\View\Template;
use Yiisoft\View\Tests\TestSupport\TestHelper;

final class PhpTemplateRendererTest extends TestCase
{
    public function testExceptionDuringRendering(): void
    {
        $renderer = new PhpTemplateRenderer();

        $view = TestHelper::createView();

        $obInitialLevel = ob_get_level();

        try {
            $renderer->render(new Template(template: __DIR__ . '/public/view/error.php', parameters: [], view: $view));
        } catch (LogicException) {
        }

        $this->assertSame(ob_get_level(), $obInitialLevel);
    }
}
