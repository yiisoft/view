<?php

return [
    'view' => [
        '__class' => \yii\view\View::class,
    ],

    \yii\view\Theme::class => Yiisoft\Factory\Definitions\Reference::to('theme'),
    'theme'                => [
        '__class' => \yii\view\Theme::class,
    ],
];
