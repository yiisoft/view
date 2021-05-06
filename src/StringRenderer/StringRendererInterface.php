<?php

declare(strict_types=1);

namespace Yiisoft\View\StringRenderer;

use Yiisoft\View\View;

interface StringRendererInterface
{
    /**
     * Renders a template string.
     *
     * This method is invoked by {@see View} whenever it tries to render a view.
     * Child classes must implement this method to render the given template string.
     *
     * @param View $view The view object used for rendering the template string.
     * @param string $template The template string.
     * @param array $parameters The parameters to be passed to the template.
     *
     * @return string The rendering result.
     */
    public function render(View $view, string $template, array $parameters): string;
}
