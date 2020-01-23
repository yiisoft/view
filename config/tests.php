<?php

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\EventDispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Log\Logger;
use Yiisoft\View\Theme;
use Yiisoft\View\View;
use Yiisoft\View\WebView;

$tempDir = sys_get_temp_dir();

return [
    ContainerInterface::class => function (ContainerInterface $container) {
        return $container;
    },

    Aliases::class => [
        '@root' => dirname(__DIR__, 1),
        '@public' => '@root/tests/public',
        '@basePath' => '@public/assets',
        '@view' => '@public/view',
        '@web' => '/baseUrl',
    ],

    ListenerProviderInterface::class => [
        '__class' => Provider::class,
    ],

    EventDispatcherInterface::class => [
        '__class' => Dispatcher::class,
        '__construct()' => [
           'listenerProvider' => Reference::to(ListenerProviderInterface::class)
        ],
    ],

    LoggerInterface::class => [
        '__class' => Logger::class,
        '__construct()' => [
            'targets' => [],
        ],
    ],

    WebView::class => function (ContainerInterface $container) {
        $aliases = $container->get(Aliases::class);
        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $theme = $container->get(Theme::class);
        $logger = $container->get(LoggerInterface::class);

        return new WebView($aliases->get('@view'), $theme, $eventDispatcher, $logger);
    },

    View::class => function (ContainerInterface $container) {
        $aliases = $container->get(Aliases::class);
        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $theme = $container->get(Theme::class);
        $logger = $container->get(LoggerInterface::class);

        return new View($aliases->get('@view'), $theme, $eventDispatcher, $logger);
    },
];
