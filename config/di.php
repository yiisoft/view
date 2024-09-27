<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Definitions\Contract\ReferenceInterface;
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
        'setParameters()' => [$params['yiisoft/view']['parameters']],
        'withRenderers()' => [$params['yiisoft/view']['renderers']],
        'withFallbackExtension()' => [$params['yiisoft/view']['defaultExtension']],
        'reset' => function (ContainerInterface $container) use ($params) {
            /** @var View $this */
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
