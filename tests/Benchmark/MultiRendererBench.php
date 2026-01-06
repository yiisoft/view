<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Benchmark;

use Yiisoft\Files\FileHelper;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\TemplateRendererInterface;
use Yiisoft\View\View;
use Yiisoft\View\ViewInterface;

final class MultiRendererBench
{
    private readonly View $view;

    public function __construct()
    {
        $basePath = __DIR__ . '/../public/tmp/multi-renderer-bench';
        FileHelper::ensureDirectory($basePath);

        $phpView = $basePath . '/view.php';
        if (!is_file($phpView)) {
            file_put_contents(
                $phpView,
                <<<'PHP_WRAP'
                <?php

                declare(strict_types=1);

                /** @var \Yiisoft\View\View $this */

                echo 'PHP view';
                PHP_WRAP
            );
        }

        $bladeView = $basePath . '/view.blade.php';
        if (!is_file($bladeView)) {
            file_put_contents($bladeView, 'Blade view');
        }

        $tplView = $basePath . '/view.tpl';
        if (!is_file($tplView)) {
            file_put_contents($tplView, 'TPL view');
        }

        $this->view = (new View($basePath, new SimpleEventDispatcher()))
            ->withRenderers([
                'blade' => new class () implements TemplateRendererInterface {
                    public function render(ViewInterface $view, string $template, array $parameters): string
                    {
                        return file_get_contents($template);
                    }
                },
                'tpl' => new class () implements TemplateRendererInterface {
                    public function render(ViewInterface $view, string $template, array $parameters): string
                    {
                        return file_get_contents($template);
                    }
                },
            ]);
    }

    public function benchRenderPhpView(): void
    {
        $this->view->render('view');
    }

    public function benchRenderBladeView(): void
    {
        $this->view->render('view.blade.php');
    }

    public function benchRenderTplView(): void
    {
        $this->view->render('view.tpl');
    }
}
