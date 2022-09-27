<?php

declare(strict_types=1);

namespace Yiisoft\View\Event\WebView;

use Yiisoft\View\WebView;

/**
 * @internal `WebViewEvent` represents events triggered by the {@see WebView} component.
 */
abstract class WebViewEvent
{
    final public function __construct(private WebView $view)
    {
    }

    final public function getView(): WebView
    {
        return $this->view;
    }
}
