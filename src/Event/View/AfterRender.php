<?php

declare(strict_types=1);

namespace Yiisoft\View\Event\View;

use Yiisoft\View\Event\AfterRenderEventInterface;
use Yiisoft\View\View;

/**
 * `AfterRender` event is triggered by {@see View::renderFile()} right after it renders a view file.
 */
final class AfterRender implements AfterRenderEventInterface
{
    private View $view;

    /**
     * @var string The view file being rendered.
     */
    private string $file;

    /**
     * @var array The parameters array passed to the {@see View::render()} or {@see View::renderFile()} method.
     */
    private array $parameters;

    private string $result;

    public function __construct(View $view, string $file, array $parameters, string $result)
    {
        $this->view = $view;
        $this->file = $file;
        $this->parameters = $parameters;
        $this->result = $result;
    }

    public function getView(): View
    {
        return $this->view;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getResult(): string
    {
        return $this->result;
    }
}
