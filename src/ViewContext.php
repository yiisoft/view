<?php

declare(strict_types=1);

namespace Yiisoft\View;

final class ViewContext implements ViewContextInterface
{
    private string $viewPath;

    public function __construct(string $viewPath)
    {
        $this->viewPath = $viewPath;
    }

    public function getViewPath(): string
    {
        return $this->viewPath;
    }
}
