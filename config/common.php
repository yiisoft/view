<?php

return [
    \yii\view\Theme::class => \yii\di\Reference::to('theme'),
    'theme' => [
        '__class' => \yii\view\Theme::class,
    ],
];
