<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Benchmark;

use Yiisoft\Files\FileHelper;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\View;

final class NestedViewsBench
{
    private readonly View $view;

    public function __construct()
    {
        $basePath = __DIR__ . '/../public/tmp/nested-bench';
        FileHelper::ensureDirectory($basePath);

        $baseView = $basePath . '/base.php';
        $subDir = $basePath . '/sub';
        FileHelper::ensureDirectory($subDir);

        if (!is_file($baseView)) {
            file_put_contents(
                $baseView,
                <<<'PHP'
<?php

declare(strict_types=1);

/** @var \Yiisoft\View\View $this */

echo $this->render('./sub/sub');
PHP
            );
        }

        $subView = $subDir . '/sub.php';
        if (!is_file($subView)) {
            file_put_contents($subView, 'nested-view-content');
        }

        $this->view = new View($basePath, new SimpleEventDispatcher());
    }

    public function benchRenderNestedView(): void
    {
        $this->view->render('base');
    }
}
