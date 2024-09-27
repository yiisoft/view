<?php

declare(strict_types=1);

namespace Yiisoft\View\Event\WebView;

use Yiisoft\View\Event\AfterRenderEventInterface;
use Yiisoft\View\WebView;

/**
 * `AfterRender` event is triggered by {@see WebView::render()} right after it renders a view file.
 */
final class AfterRender implements AfterRenderEventInterface
{
    /**
     * @param string $file The view file being rendered.
     * @param array $parameters The parameters array passed to the {@see WebView::render()} method.
     */
    public function __construct(
        private readonly WebView $view,
        private readonly string $file,
        private readonly array $parameters,
        private readonly string $result
    ) {
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

    public function getResult(): string
    {
        return $this->result;
    }
}
