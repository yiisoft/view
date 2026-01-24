<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\View\DoubleExtension;

use Yiisoft\View\TemplateRendererInterface;
use Yiisoft\View\ViewInterface;

final class TemplateRendererSpy implements TemplateRendererInterface
{
    private array $templates = [];

    public function getTemplates(): array
    {
        return $this->templates;
    }

    public function render(ViewInterface $view, string $template, array $parameters): string
    {
        $this->templates[] = $template;
        return file_get_contents($template);
    }
}
