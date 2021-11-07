<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\View\ViewContext;

final class ViewContextTest extends TestCase
{
    public function testBase(): void
    {
        $context = new ViewContext(__DIR__);

        $this->assertSame(__DIR__, $context->getViewPath());
    }
}
