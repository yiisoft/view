<?php

use Yiisoft\Asset\AssetConverter;
use Yiisoft\Asset\AssetManager;
use Yiisoft\Factory\Definitions\Reference;

return [
    AssetConverter::class => [
        '__class' => AssetConverter::class,
        '__construct()' => [Reference::to(Aliases::class)]
    ],

    AssetManager::class => [
        '__class' => AssetManager::class,
        '__construct()' => [Reference::to(Aliases::class)]
    ],
];
