<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Benchmark;

use PhpBench\Attributes as Bench;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\WebView;

final class WebViewAssetsBench
{
    private WebView $view;
    private WebView $preparedView;

    public function __construct()
    {
        $this->view = new WebView(__DIR__ . '/../public/view', new SimpleEventDispatcher());
        $this->preparedView = $this->view;
    }

    #[Bench\BeforeMethods(['prepareView'])]
    public function prepareView(): void
    {
        $view = $this->view->withClearedState();

        $view->addCssFiles([
            'file-1.css',
            ['file-2.css', 'crossorigin' => 'anonymous', WebView::POSITION_BEGIN],
        ]);

        $view->addJsFiles([
            'file-1.js',
            ['file-2.js', 'async' => true, WebView::POSITION_BEGIN],
        ]);

        $view->addCssStrings([
            '.a1 { color: red; }',
            ['.a2 { color: red; }', WebView::POSITION_HEAD],
        ]);

        $view->addJsStrings([
            'uniqueName' => 'app1.start();',
            'app2.start();',
        ]);

        $this->preparedView = $view;
    }

    public function benchRegisterAssetsAndRender(): void
    {
        $this->preparedView->render('positions.php');
    }
}
