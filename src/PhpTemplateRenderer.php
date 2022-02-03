<?php

declare(strict_types=1);

namespace Yiisoft\View;

use Throwable;

use function extract;
use function func_get_arg;
use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_implicit_flush;
use function ob_start;

/**
 * `PhpTemplateRenderer` renders the PHP views.
 */
final class PhpTemplateRenderer implements TemplateRendererInterface
{
    public function render(ViewInterface $view, string $template, array $parameters): string
    {
        $renderer = function (): void {
            /** @psalm-suppress MixedArgument */
            extract(func_get_arg(1), EXTR_OVERWRITE);
            /** @psalm-suppress UnresolvableInclude */
            require func_get_arg(0);
        };

        $obInitialLevel = ob_get_level();
        ob_start();
        /** @psalm-suppress InvalidArgument */
        PHP_VERSION_ID >= 80000 ? ob_implicit_flush(false) : ob_implicit_flush(0);
        try {
            /** @psalm-suppress PossiblyInvalidFunctionCall */
            $renderer->bindTo($view)($template, $parameters);
            return ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $obInitialLevel) {
                ob_end_clean();
            }
            throw $e;
        }
    }
}
