<?php

declare(strict_types=1);

namespace Yiisoft\View;

final class Template
{
    /**
     * @param string $template The template file.
     * @param array $parameters The parameters to be passed to the view file.
     * @param ViewInterface $view The view instance used for rendering the file.
     * @param ViewContextInterface|null $viewContext The context instance of the view.
     */
    public function __construct(
        private string $template,
        private array $parameters,
        private ViewInterface $view,
        private ?ViewContextInterface $viewContext = null
    ) {
    }

    public function getTemplate(): string
    {
        return $this->template;
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
