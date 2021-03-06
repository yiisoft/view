<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Event\View;

use PHPUnit\Framework\TestCase;
use Yiisoft\View\Event\View\AfterRender;
use Yiisoft\View\Tests\TestSupport\TestHelper;

final class AfterRenderTest extends TestCase
{
    public function testBase(): void
    {
        $view = TestHelper::createView();
        $file = '/test.php';
        $parameters = ['planet' => 'Earth'];
        $result = 'My planet is Earth!';

        $event = new AfterRender($view, $file, $parameters, $result);

        $this->assertSame($view, $event->getView());
        $this->assertSame($file, $event->getFile());
        $this->assertSame($parameters, $event->getParameters());
        $this->assertSame($result, $event->getResult());
    }
}
