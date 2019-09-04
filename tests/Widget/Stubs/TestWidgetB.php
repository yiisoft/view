<?php
declare(strict_types = 1);

namespace Yiisoft\Widget\Tests\Stubs;

use Yiisoft\Widget\Widget;

/**
 * TestWidgetB
 */
class TestWidgetB extends Widget
{
    /**
     * @var string $id;
     */
    private $id;

    public function init(): void
    {
    }

    public function run(): string
    {
        return '<run-' . $this->id . '>';
    }

    public function id(string $value): Widget
    {
        $this->id = $value;

        return $this;
    }
}
