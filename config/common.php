<?php

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Asset\AssetConverter;
use Yiisoft\Asset\AssetManager;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Log\Logger;

return [
    Aliases::class => [
    ],

    AssetConverter::class => [
        '__class' => AssetConverter::class,
        '__construct()' => [
            Reference::to(Aliases::class),
            Reference::to(LoggerInterface::class)
        ]
    ],

    AssetConverterInterface::class => AssetConverter::class,

    AssetManager::class => function (ContainerInterface $container) {
        $assetConverterInterface = $container->get(AssetConverterInterface::class);
        $assetManager = new AssetManager();
        $assetManager->setConverter($assetConverterInterface);

        return $assetManager;
    },

    LoggerInterface::class => [
        '__class' => Logger::class,
        '__construct()' => [
            'targets' => [],
        ],
    ],
];
