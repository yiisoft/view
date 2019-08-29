<?php

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Asset\AssetConverter;
use Yiisoft\Asset\AssetConverterInterface;
use Yiisoft\Asset\AssetManager;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Log\Logger;
use Yiisoft\Widget\Widget;

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
        $aliases = $container->get(Aliases::class);
        $logger = $container->get(LoggerInterface::class);
        $assetManager = new AssetManager($aliases, $logger);
        $assetManager->setConverter($assetConverterInterface);

        return $assetManager;
    },

    LoggerInterface::class => [
        '__class' => Logger::class,
        '__construct()' => [
            'targets' => [],
        ],
    ],

    Widget::class => [
        '__class' => Widget::class,
        '__construct()' => [
            Reference::to(EventDispatcherInterface::class),
        ]
    ],
];
