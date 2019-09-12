<?php

namespace Yiisoft\View;

interface TemplateRendererInterface
{
    /**
     * Renders a template file.
     *
     * This method is invoked by [[View]] whenever it tries to render a view.
     * Child classes must implement this method to render the given view file.
     *
     * @param View $view the view object used for rendering the file.
     * @param string $template the template file.
     * @param array $params the parameters to be passed to the view file.
     *
     * @return string the rendering result
     */
    public function render(View $view, string $template, array $params): string;
}
