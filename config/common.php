<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\View\View;

/** @var array $params */

return [
    View::class => [
        '__construct()' => [
            'basePath' => DynamicReference::to(
                static fn (Aliases $aliases) => $aliases->get($params['yiisoft/view']['basePath'])
            ),
        ],
        'setParameters()' => [
            $params['yiisoft/view']['parameters'],
        ],
        'reset' => function () use ($params) {
            /** @var View $this */
            $this->clear();
            $this->setParameters($params['yiisoft/view']['parameters']);
        },
    ],
];
