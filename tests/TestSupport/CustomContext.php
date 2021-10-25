<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\TestSupport;

use Yiisoft\View\ViewContextInterface;

use function dirname;

final class CustomContext implements ViewContextInterface
{
    public function getViewPath(): string
    {
        return dirname(__DIR__) . '/public/view/custom-context';
    }
}
