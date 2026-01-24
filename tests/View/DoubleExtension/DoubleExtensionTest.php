<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\View\DoubleExtension;

use PHPUnit\Framework\TestCase;
use Yiisoft\View\PhpTemplateRenderer;
use Yiisoft\View\View;

final class DoubleExtensionTest extends TestCase
{
    public function testBase(): void
    {
        $renderer = new TemplateRendererSpy();
        $view = (new View(__DIR__ . '/views'))
            ->withRenderers([
                'blade.php' => $renderer,
            ]);

        $view->render('two.blade.php');

        $this->assertSame(
            [__DIR__ . '/views/two.blade.php'],
            $renderer->getTemplates(),
        );
    }

    public function testWithSeveralRenderers1(): void
    {
        $renderer = new TemplateRendererSpy();
        $view = (new View(__DIR__ . '/views'))
            ->withRenderers([
                'blade.php' => $renderer,
                'php' => new PhpTemplateRenderer(),
            ]);

        $view->render('two.blade.php');

        $this->assertSame(
            [__DIR__ . '/views/two.blade.php'],
            $renderer->getTemplates(),
        );
    }

    public function testWithSeveralRenderers2(): void
    {
        $renderer = new TemplateRendererSpy();
        $view = (new View(__DIR__ . '/views'))
            ->withRenderers([
                'php' => new PhpTemplateRenderer(),
                'blade.php' => $renderer,
            ]);

        $view->render('two.blade.php');

        $this->assertSame(
            [__DIR__ . '/views/two.blade.php'],
            $renderer->getTemplates(),
        );
    }
}
