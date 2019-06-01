<?php

namespace Yiisoft\View\View\Event;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * BeforeRender event is triggered by [[View::renderFile()]] right before it renders a view file.
 */
class BeforeRender extends ViewEvent implements StoppableEventInterface
{
    private $stopPropagation = false;

    public function stopPropagation(): void
    {
        $this->stopPropagation = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopPropagation;
    }
}
