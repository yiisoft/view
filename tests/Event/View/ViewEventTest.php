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
        $parameters = ['planet' => 'Earth'];

        $event = $this->createEvent($view, $parameters);

        $this->assertSame($view, $event->getView());
        $this->assertSame($parameters, $event->getParameters());
    }

    abstract protected function createEvent(View $view, array $parameters): ViewEvent;
}
