<?php

declare(strict_types=1);

namespace Yiisoft\View\Event;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * BeforeRender event is triggered by {@see View::renderFile()} right before it renders a view file.
 */
class BeforeRender extends ViewEvent implements StoppableEventInterface
{
    private bool $stopPropagation = false;

    public function stopPropagation(): void
    {
        $this->stopPropagation = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopPropagation;
    }
}
