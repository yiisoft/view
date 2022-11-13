<?php

declare(strict_types=1);

namespace Yiisoft\View\Event\View;

use Psr\EventDispatcher\StoppableEventInterface;
use Yiisoft\View\View;

/**
 * `BeforeRender` event is triggered by {@see View::renderFile()} right before it renders a view file.
 */
final class BeforeRender implements StoppableEventInterface
{
    private bool $stopPropagation = false;

    /**
     * @param string $file The view file being rendered.
     * @param array $parameters The parameters array passed to the {@see View::render()} or {@see View::renderFile()}
     * method.
     */
    public function __construct(
        private View $view,
        private string $file,
        private array $parameters
    ) {
    }

    public function stopPropagation(): void
    {
        $this->stopPropagation = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopPropagation;
    }

    public function getView(): View
    {
        return $this->view;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
