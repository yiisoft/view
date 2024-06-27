<?php

declare(strict_types=1);

namespace Yiisoft\View\Event\WebView;

use Psr\EventDispatcher\StoppableEventInterface;
use Yiisoft\View\WebView;

/**
 * `BeforeRender` event is triggered by {@see WebView::render()} right before it renders a view file.
 */
final class BeforeRender implements StoppableEventInterface
{
    private bool $stopPropagation = false;

    /**
     * @param string $file The view file being rendered.
     * @param array $parameters The parameters array passed to the {@see WebView::render()} method.
     */
    public function __construct(
        private WebView $view,
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

    public function getView(): WebView
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
