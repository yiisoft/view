<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Benchmark;

use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\Theme;
use Yiisoft\View\View;

final class ViewThemeBench
{
    private View $view;

    public function __construct()
    {
        $basePath = __DIR__ . '/../public/view';
        $themePath = __DIR__ . '/../public/tmp/theme-basic/views';

        @mkdir($themePath, 0777, true);

        // ensure themed file exists
        $sourceFile = $basePath . '/only-content.php';
        $themedFileDir = dirname($themePath . '/only-content.php');
        @mkdir($themedFileDir, 0777, true);
        if (is_file($sourceFile) && !is_file($themePath . '/only-content.php')) {
            copy($sourceFile, $themePath . '/only-content.php');
        }

        $theme = new Theme([
            $basePath => $themePath,
        ]);

        $this->view = new View($basePath, new SimpleEventDispatcher());
        $this->view->setTheme($theme);
    }

    public function benchRenderThemedView(): void
    {
        $this->view->render('only-content', [
            'content' => 'benchmark',
        ]);
    }
}
