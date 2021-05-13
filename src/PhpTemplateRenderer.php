<?php

declare(strict_types=1);

namespace Yiisoft\View;

class PhpTemplateRenderer implements TemplateRendererInterface
{
    public function render(BaseView $view, string $template, array $params): string
    {
        $renderer = function () {
            extract(func_get_arg(1), EXTR_OVERWRITE);
            require func_get_arg(0);
        };

        $obInitialLevel = ob_get_level();
        ob_start();
        PHP_VERSION_ID >= 80000 ? ob_implicit_flush(false) : ob_implicit_flush(0);
        try {
            $renderer->bindTo($view)($template, $params);
            return ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() > $obInitialLevel) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        }
    }
}
