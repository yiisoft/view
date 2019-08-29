<?php
declare(strict_types = 1);

namespace Yiisoft\Widget\Tests\Stubs;

use Yiisoft\Widget\Widget;

/**
 * TestWidgetB
 */
class TestWidgetB extends Widget
{
    public function init(): void
    {
    }

    public function run(): string
    {
        return '<run-' . $this->getId() . '>';
    }
}
