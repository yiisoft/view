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

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return ViewInterface
     */
    public function getView(): ViewInterface
    {
        return $this->view;
    }

    /**
     * @return ViewContextInterface|null
     */
    public function getViewContext(): ?ViewContextInterface
    {
        return $this->viewContext;
    }
}
