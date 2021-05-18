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
    private View $view;

    /**
     * @var string The view file being rendered.
     */
    private string $file;

    /**
     * @var array The parameters array passed to the {@see View::render()} or {@see View::renderFile()} method.
     */
    private array $parameters;

    private bool $stopPropagation = false;

    public function __construct(View $view, string $file, array $parameters)
    {
        $this->view = $view;
        $this->file = $file;
        $this->parameters = $parameters;
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
