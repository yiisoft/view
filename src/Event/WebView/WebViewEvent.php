<?php

declare(strict_types=1);

namespace Yiisoft\View\Event\WebView;

use Yiisoft\View\WebView;

/**
 * @internal `WebViewEvent` represents events triggered by the {@see WebView} component.
 */
abstract class WebViewEvent
{
    private WebView $view;

    /**
     * @var array The parameters array passed to the event.
     */
    private array $parameters;

    final public function __construct(WebView $view, array $parameters)
    {
        $this->view = $view;
        $this->parameters = $parameters;
    }

    final public function getView(): WebView
    {
        return $this->view;
    }

    final public function getParameters(): array
    {
        return $this->parameters;
    }
}
