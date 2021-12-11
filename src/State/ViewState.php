<?php

declare(strict_types=1);

namespace Yiisoft\View\State;

final class ViewState
{
    use StateTrait;

    public function clear(): void
    {
        $this->parameters = [];
        $this->blocks = [];
    }
}
