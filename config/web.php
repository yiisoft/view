<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\Definitions\DynamicReference;
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

    WebView::class => [
        '__construct()' => [
            'basePath' => DynamicReference::to(
                static fn (Aliases $aliases) => $aliases->get($params['yiisoft/view']['basePath'])
            ),
        ],
        'setParameters()' => [
            $params['yiisoft/view']['parameters'],
        ],
        'reset' => function () use ($params) {
            /** @var WebView $this */
            $this->clear();
            $this->setParameters($params['yiisoft/view']['parameters']);
        },
    ],
];
