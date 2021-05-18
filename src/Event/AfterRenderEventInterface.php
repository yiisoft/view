<?php

declare(strict_types=1);

namespace Yiisoft\View\Event;

/**
 * @internal
 */
interface AfterRenderEventInterface
{
    public function getResult(): string;
}
