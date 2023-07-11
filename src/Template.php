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

    /**
     * Get the full absolute path of the view template file.
     *
     * @return string The full absolute path of the view template file.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the parameters to pass to the template.
     *
     * @return array The parameters to pass to the template.
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get the view instance used for rendering the file.
     *
     * @return ViewInterface The view instance used for rendering the file.
     */
    public function getView(): ViewInterface
    {
        return $this->view;
    }

    /**
     * Get the context instance of the view.
     *
     * @return ViewContextInterface|null The context instance of the view.
     */
    public function getViewContext(): ?ViewContextInterface
    {
        return $this->viewContext;
    }
}
