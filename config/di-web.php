<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Definitions\Contract\ReferenceInterface;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\View\Theme;
use Yiisoft\View\WebView;

/** @var array $params */

return [
    Theme::class => static function (Aliases $aliases) use ($params) {
        $pathMap = [];

        foreach ($params['yiisoft/view']['theme']['pathMap'] as $key => $value) {
            if (is_array($value)) {
                $pathMap[$aliases->get($key)] = $aliases->getArray($value);
            } else {
                $pathMap[$aliases->get($key)] = $aliases->get($value);
            }
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
        'setParameters()' => [$params['yiisoft/view']['parameters']],
        'withRenderers()' => [$params['yiisoft/view']['renderers']],
        'withFallbackExtension()' => [...(array) $params['yiisoft/view']['fallbackExtension']],
        'reset' => function (ContainerInterface $container) use ($params) {
            /** @var WebView $this */
            $this->clear();
            $parameters = $params['yiisoft/view']['parameters'];
            foreach ($parameters as $name => $parameter) {
                $parameters[$name] = $parameter instanceof ReferenceInterface ?
                    $parameter->resolve($container) :
                    $parameter;
            }
            $this->setParameters($parameters);
        },
    ],
];
