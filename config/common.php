<?php

declare(strict_types=1);

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\View\View;

/** @var array $params */

return [
    View::class => static function (
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger,
        Aliases $aliases
    ) use ($params): View {
        $view = new View(
            $aliases->get($params['yiisoft/view']['basePath']),
            $eventDispatcher,
            $logger,
        );

        foreach ($params['yiisoft/view']['commonParameters'] as $id => $value) {
            $view->setCommonParameter($id, $value);
        }

        return $view;
    },
];
