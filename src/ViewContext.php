<?php

declare(strict_types=1);

namespace Yiisoft\View;

final class ViewContext implements ViewContextInterface
{
    public function __construct(private string $viewPath)
    {
    }

    public function getViewPath(): string
    {
        return $this->viewPath;
    }
}
