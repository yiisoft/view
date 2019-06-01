<?php
namespace Yiisoft\Widget\Event;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * BeforeRun event is raised right before executing a widget.
 * @package yii\widgets
 */
class BeforeRun implements StoppableEventInterface
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
