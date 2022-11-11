<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php70\Rector\FuncCall\NonVariableToVariableOnFunctionCallRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_80,
    ]);

    $rectorConfig->skip([
        NonVariableToVariableOnFunctionCallRector::class => [__DIR__ . '/src/PhpTemplateRenderer.php'],
        RemoveExtraParametersRector::class => [__DIR__ . '/src/PhpTemplateRenderer.php'],
    ]);
};
