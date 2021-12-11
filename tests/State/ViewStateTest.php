<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\State;

use PHPUnit\Framework\TestCase;
use Yiisoft\View\State\ViewState;

final class ViewStateTest extends TestCase
{
    public function testClear(): void
    {
        $state = new ViewState();
        $state->setBlock('name', 'Mike');
        $state->setParameter('age', 42);

        $state->clear();

        $this->assertFalse($state->hasBlock('name'));
        $this->assertFalse($state->hasParameter('age'));
    }
}
