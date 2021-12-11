<?php

declare(strict_types=1);

namespace Yiisoft\View\State;

final class WebViewState
{
    use StateTrait;

    public function clear(): void
    {
        $this->parameters = [];
        $this->blocks = [];
    }
}
