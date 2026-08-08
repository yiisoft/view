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
    ->ignoreErrorsOnPackages(['psr/event-dispatcher'], [ErrorType::SHADOW_DEPENDENCY]);
