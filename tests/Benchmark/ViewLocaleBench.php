<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Benchmark;

use Yiisoft\Files\FileHelper;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\View;

final class ViewLocaleBench
{
    private View $view;

    public function __construct()
    {
        $basePath = __DIR__ . '/../public/tmp/locale-bench';

        FileHelper::ensureDirectory($basePath . '/es');

        file_put_contents($basePath . '/file.php', 'test en render');
        file_put_contents($basePath . '/es/file.php', 'test es render');

        $this->view = new View($basePath, new SimpleEventDispatcher());
        $this->view->setLocale('es');
    }

    public function benchRenderLocalizedView(): void
    {
        $this->view->render('file');
    }
}
