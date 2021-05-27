<?php

declare(strict_types=1);

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\View\Theme;
use Yiisoft\View\WebView;

/** @var array $params */

return [
    Theme::class => static function (Aliases $aliases) use ($params) {
        $pathMap = [];

        foreach ($params['yiisoft/view']['theme']['pathMap'] as $key => $value) {
            $pathMap[$aliases->get($key)] = $aliases->get($value);
        }

        return new Theme(
            $pathMap,
            $params['yiisoft/view']['theme']['basePath'],
            $params['yiisoft/view']['theme']['baseUrl']
        );
    },

    WebView::class => static function (
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger,
        Aliases $aliases
    ) use ($params): WebView {
        $webView = new WebView(
            $aliases->get($params['yiisoft/view']['basePath']),
            $eventDispatcher,
            $logger,
        );

        foreach ($params['yiisoft/view']['commonParameters'] as $id => $value) {
            $webView->setCommonParameter($id, $value);
        }

        return $webView;
    },
];
