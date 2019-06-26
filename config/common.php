<?php

use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\View\Theme;
use Yiisoft\View\View;

return [
    'view' => [
        '__class' => View::class,
    ],

    Theme::class => Reference::to('theme'),
    'theme'                => [
        '__class' => Theme::class,
    ],
];
