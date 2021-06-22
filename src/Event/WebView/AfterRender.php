<?php

declare(strict_types=1);

namespace Yiisoft\View\Event\WebView;

use Yiisoft\View\Event\AfterRenderEventInterface;
use Yiisoft\View\WebView;

/**
 * `AfterRender` event is triggered by {@see WebView::renderFile()} right after it renders a view file.
 */
final class AfterRender implements AfterRenderEventInterface
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

    private string $result;

    public function __construct(WebView $view, string $file, array $parameters, string $result)
    {
        $this->view = $view;
        $this->file = $file;
        $this->parameters = $parameters;
        $this->result = $result;
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
