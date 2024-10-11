<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\View\ResetContext;

use PHPUnit\Framework\TestCase;
use Yiisoft\View\View;
use Yiisoft\View\ViewContext;

final class ResetContextTest extends TestCase
{
    public function testBase(): void
    {
        $baseView = (new View(__DIR__.'/views-base'))
            ->withContext(new ViewContext(__DIR__.'/views-context'));

        $view = $baseView->withContext(null);

        $baseContent = $baseView->render('test');
        $content = $view->render('test');

        $this->assertSame('View Context', $baseContent);
        $this->assertSame('View Base', $content);
    }
}
