<?php

declare(strict_types=1);

namespace Yiisoft\View;

/**
 * The template holds the information needed to render a view.
 */
final class Template
{
    /**
     * @param string $path The full absolute path of the view template file.
     * @param array $parameters The parameters to pass to the template.
     * @param ViewInterface $view The view instance used for rendering the file.
     * @param ViewContextInterface|null $viewContext The context instance of the view.
     */
    public function __construct(
        private string $path,
        private array $parameters,
        private ViewInterface $view,
        private ?ViewContextInterface $viewContext = null
    ) {
    }

    public function getTemplate(): string
    {
        return $this->path;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getView(): ViewInterface
    {
        return $this->view;
    }

    public function getViewContext(): ?ViewContextInterface
    {
        return $this->viewContext;
    }
}
