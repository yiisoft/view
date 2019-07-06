<?php
namespace Yiisoft\View;


class PhpTemplateRenderer implements TemplateRenderer
{
    public function render(View $view, string $template, array $params): string
    {
        $renderer = function () use ($template, $params) {
            extract($params, EXTR_OVERWRITE);
            require $template;
        };

        $_obInitialLevel_ = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        try {
            $renderer->bindTo($view)();
            return ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() > $_obInitialLevel_) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        }
    }
}
