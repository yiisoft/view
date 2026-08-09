<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/config', isDev: false)
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // View/WebView optionally accept a PSR-14 event dispatcher without requiring the package at runtime;
    // previously whitelisted the same way in composer-require-checker.json. See "suggest" in composer.json.
    ->ignoreErrorsOnPackages(['psr/event-dispatcher'], [ErrorType::SHADOW_DEPENDENCY])
    // `yiisoft/definitions` is used only in `config/di.php` and `config/di-web.php`, which are loaded by
    // consumers using `yiisoft/di`, that already requires `yiisoft/definitions` itself.
    ->ignoreErrorsOnPackages(['yiisoft/definitions'], [ErrorType::SHADOW_DEPENDENCY])
    // `psr/container` is used only in `config/di.php`, which is loaded by consumers using `yiisoft/di`,
    // that already requires `psr/container` itself.
    ->ignoreErrorsOnPackageAndPaths(
        'psr/container',
        [__DIR__ . '/config/di.php', __DIR__ . '/config/di-web.php'],
        [ErrorType::SHADOW_DEPENDENCY],
    )
    // `yiisoft/aliases` is used only in `config/di.php`, which is loaded by consumers using `yiisoft/di`,
    // that already requires `yiisoft/aliases` itself.
    ->ignoreErrorsOnPackageAndPaths(
        'yiisoft/aliases',
        [__DIR__ . '/config/di.php', __DIR__ . '/config/di-web.php'],
        [ErrorType::DEV_DEPENDENCY_IN_PROD],
    );
