## Config AssetManager <span id="config-asset-manager"></span>

The AssetManager and AssetConverter component to configure it must be added to the common.php file of your configuration, to both must pass as a reference to the __construct() Aliases::class and Logger::class, then we will explain the options that you can adjust.

```php
common.php:

<?php

use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Asset\AssetConverter;
use Yiisoft\Asset\AssetManager;
use Yiisoft\Factory\Definitions\Reference;
use Yiisoft\Log\Logger;

return [
    Aliases::class => [
    ],

    AssetConverter::class => [
        '__class' => AssetConverter::class,
        '__construct()' => [
            Reference::to(Aliases::class),
            Reference::to(LoggerInterface::class)
        ]
    ],

    AssetManager::class => [
        '__class' => AssetManager::class,
        '__construct()' => [
            Reference::to(Aliases::class),
            Reference::to(LoggerInterface::class)
        ],
        // [array] path optional directories assets.
        'setAlternativesAlias()' = [
            [
                '@npm' => '@root/node_modules'
            ],
        ],
        // [bool] Whether to append a timestamp to the URL of every published asset.
        'setAppendTimestamp()' = [false], 
        // [array] Mapping from source asset files (keys) to target asset files (values).
        'setAssetMap()' = [[]],
        // [string] The root directory storing the published asset files.
        'setBasePath()' = ['@basePath'],
        // [string] The base URL through which the published asset files can be accessed.
        'setBaseUrl()' = ['@web'],
        // [integer] The permission to be set for newly generated asset directories.
        'setDirMode()' = [0775],
        // [integer] The permission to be set for newly published asset files.
        'setFileMode()' = [0755],
        // [callable] A callback that will be called to produce hash for asset directory generation.
        'setHashCallback()' = [],
        // [bool] Whether to use symbolic link to publish asset files.
        'setLinkAssets()' = [false],
    ],

    LoggerInterface::class => [
        '__class' => Logger::class,
        '__construct()' => [
            'targets' => [],
        ],
    ],
];
```

Then in ViewFactory we must configure the assetmanager for the WebView::class

Example:

```
<?php
declare(strict_types = 1);

namespace Yii\Factories;

use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Asset\AssetManager;
use Yiisoft\Aliases\Aliases;
use Yiisoft\View\Theme;
use Yiisoft\View\WebView;

class ViewFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $aliases = $container->get(Aliases::class);
        $assetManager = $container->get(AssetManager::class);
        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        $logger = $container->get(LoggerInterface::class);
        $theme = $container->get(Theme::class);
        $view = $aliases->get('@views');
        $webView = new WebView($view, $theme, $eventDispatcher, $logger);
        $webView->setAssetManager($assetManager);

        return $webView;
    }
}
```
