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
     * {@see View} and {@see WebView} invoke this method whenever it tries to render a view.
     * The classes must implement this method to render the given template file.
     *
     * @param ViewInterface $view The view instance used for rendering the file.
     * @param string $template The template file to render.
     * @param array $parameters The parameters to be passed to the view file.
     *
     * @return string The rendering result.
     */
    public function render(ViewInterface $view, string $template, array $parameters): string;
}
