<?php

declare(strict_types=1);

namespace Yiisoft\View\Event\WebView;

use Psr\EventDispatcher\StoppableEventInterface;
use Yiisoft\View\WebView;

/**
 * `BeforeRender` event is triggered by {@see WebView::renderFile()} right before it renders a view file.
 */
final class BeforeRender implements StoppableEventInterface
{
    private WebView $view;

    /**
     * @var string The view file being rendered.
     */
    private string $file;

    /**
     * @var array The parameters array passed to the {@see WebView::render()} or {@see WebView::renderFile()} method.
     */
    private array $parameters;

    private bool $stopPropagation = false;

    public function __construct(WebView $view, string $file, array $parameters)
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
