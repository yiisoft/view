<?php

declare(strict_types=1);

namespace Yiisoft\View;

/**
 * `TemplateRendererInterface` is the interface that should be implemented by view template renderers.
 */
interface TemplateRendererInterface
{
    /**
     * Renders a template file.
     *
     * This method is invoked by {@see View} and {@see WebView} whenever it tries to render a view.
     * The classes must implement this method to render the given view file.
     *
     *
     * @return string The rendering result.
     */
    public function render(Template $template): string;
}
