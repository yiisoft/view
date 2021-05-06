<?php

declare(strict_types=1);

namespace Yiisoft\View\StringRenderer;

use Yiisoft\View\View;

final class NoRenderStringRenderer implements StringRendererInterface
{
    public function render(View $view, string $template, array $parameters): string
    {
        return $template;
    }
}
