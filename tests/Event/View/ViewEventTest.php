<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Event\View;

use PHPUnit\Framework\TestCase;
use Yiisoft\View\Event\View\ViewEvent;
use Yiisoft\View\Tests\TestSupport\TestHelper;
use Yiisoft\View\View;

abstract class ViewEventTest extends TestCase
{
    public function testBase(): void
    {
        $view = TestHelper::createView();

        $event = $this->createEvent($view);

        $this->assertSame($view, $event->getView());
    }

    abstract protected function createEvent(View $view): ViewEvent;
}
