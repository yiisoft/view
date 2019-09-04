<?php
declare(strict_types = 1);

namespace Yiisoft\Widget\Tests\Stubs;

use Yiisoft\Widget\Widget;

/**
 * TestWidget
 * @method static TestWidget widget()
 */
class TestWidget extends Widget
{
    /**
     * @var string $id
     */
    private $id;

    public function getContent(): string
    {
        return '<run-' . $this->id . '>';
    }

    public function id(string $value): Widget
    {
        $this->id = $value;

        return $this;
    }
}
