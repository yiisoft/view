<?php

declare(strict_types=1);

namespace Yiisoft\View\Event;

/**
 * AfterRender event is triggered by {@see View::renderFile()} and {@see View::renderString()}
 * right after it renders.
 */
class AfterRender extends ViewEvent
{
    private string $result;

    public function __construct(?string $file, array $parameters, string $result)
    {
        $this->result = $result;
        parent::__construct($file, $parameters);
    }

    public function getResult(): string
    {
        return $this->result;
    }
}
