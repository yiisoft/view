<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\TestSupport;

use Yiisoft\Files\FileHelper;

use function dirname;

final class TestHelper
{
    public static function touch(string $path): void
    {
        FileHelper::ensureDirectory(dirname($path));
        touch($path);
    }
}
