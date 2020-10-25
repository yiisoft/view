<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\View\Theme;

/** @var array $params */

return [
    Theme::class => static function (Aliases $aliases) use ($params) {
        $pathMap = [];

        foreach ($params['yiisoft/view']['theme']['pathMap'] as $key => $value) {
            $pathMap = [
                $aliases->get($key) => $aliases->get($value)
            ];
        }

        return new Theme(
            $pathMap,
            $params['yiisoft/view']['theme']['basePath'],
            $params['yiisoft/view']['theme']['baseUrl']
        );
    },

    WebView::class => static fn (
        Aliases $aliases,
        EventDispatcherInterface $event,
        LoggerInterface $logger,
        Theme $theme
    ) => new WebView($aliases->get('@views'), $theme, $event, $logger)
];
