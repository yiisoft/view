<?php

declare(strict_types=1);

namespace Yiisoft\View;

final class PhpEvalStringRenderer implements StringRendererInterface
{
    public function render(View $view, string $template, array $parameters): string
    {
        $renderer = function () {
            extract(func_get_arg(1), EXTR_OVERWRITE);
            eval(func_get_arg(0));
        };

        $obInitialLevel = ob_get_level();
        ob_start();
        PHP_VERSION_ID >= 80000 ? ob_implicit_flush(false) : ob_implicit_flush(0);
        try {
            $renderer->bindTo($view)($template, $parameters);
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
