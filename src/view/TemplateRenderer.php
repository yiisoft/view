<?php
namespace Yiisoft\View\View;

interface TemplateRenderer
{
    /**
     * Renders a view file.
     *
     * This method is invoked by [[View]] whenever it tries to render a view.
     * Child classes must implement this method to render the given view file.
     *
     * @param Template $template the view object used for rendering the file.
     * @param string $file the template file.
     * @param array $params the parameters to be passed to the view file.
     *
     * @return string the rendering result
     */
    public function render(Template $template, string $file, array $params): string;
}
