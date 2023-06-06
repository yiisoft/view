<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\View\Template;
use Yiisoft\View\Tests\TestSupport\TestHelper;
use Yiisoft\View\ViewContext;

final class TemplateTest extends TestCase
{
    public function testTemplate(): void
    {
        $template = new Template(
            $file = __DIR__ . '/public/view/error.php',
            ['foo' => 'bar'],
            $view = TestHelper::createView(),
            new ViewContext(__DIR__)
        );

        $this->assertSame($file, $template->getTemplate());
        $this->assertSame(['foo' => 'bar'], $template->getParameters());
        $this->assertSame($view, $template->getView());
        $this->assertSame(__DIR__, $template->getViewContext()->getViewPath());
    }
}
