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
            /** @psalm-suppress MixedArgument, PossiblyFalseArgument */
            extract(func_get_arg(1), EXTR_OVERWRITE);
            /** @psalm-suppress UnresolvableInclude */
            require func_get_arg(0);
        };

        $obInitialLevel = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        try {
            /** @psalm-suppress PossiblyInvalidFunctionCall,PossiblyNullFunctionCall */
            $renderer->bindTo($view)($template, $parameters);

            /**
             * @var string We assume that in this case active output buffer is always existed, so `ob_get_clean()`
             * returns a string.
             */
            return ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $obInitialLevel) {
                ob_end_clean();
            }
            throw $e;
        }
    }
}
