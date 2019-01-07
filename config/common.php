<?php

return [
    'view' => [
        '__class' => \yii\view\View::class,
    ],

    \yii\view\Theme::class => \yii\di\Reference::to('theme'),
    'theme' => [
        '__class' => \yii\view\Theme::class,
    ],
];
