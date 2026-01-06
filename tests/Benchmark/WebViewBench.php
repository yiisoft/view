<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Benchmark;

use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\WebView;

final class WebViewBench
{
    private WebView $view;

    public function __construct()
    {
        $this->view = new WebView(__DIR__ . '/../public/view', new SimpleEventDispatcher());
        $this->view->setTitle('Posts');
    }

    public function benchRenderLayout(): void
    {
        $this->view->render('layout', [
            'content' => 'content',
        ]);
    }
}
