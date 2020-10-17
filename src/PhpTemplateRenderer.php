<?php

declare(strict_types=1);

namespace Yiisoft\View;

use Yiisoft\View\Exception\ContentCantBeFetched;

class PhpTemplateRenderer implements TemplateRendererInterface
{
    public function render(View $view, string $template, array $params): string
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

            $content = ob_get_clean();
            if (is_string($content)) {
                return $content;
            } else {
                throw new ContentCantBeFetched();
            }
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
