<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Benchmark;

use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\View;

final class ViewBasicBench
{
    private View $view;

    public function __construct()
    {
        $this->view = new View(__DIR__ . '/../public/view', new SimpleEventDispatcher());
    }

    public function benchRenderSimpleView(): void
    {
        $this->view->render('only-content', [
            'content' => 'benchmark',
        ]);
    }
}
