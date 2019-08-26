## Asset Bundles <span id="asset-bundles"></span>

Yii manages assets in the unit of *asset bundle*. An asset bundle is simply a collection of assets located in a directory. When you register an asset bundle, it will include the CSS and JavaScript files in the bundle in the rendered Web page.


## Defining Asset Bundles <span id="defining-asset-bundles"></span>

Asset bundles are specified as PHP classes extending from [Yiisoft\Asset\AssetBundle]. The name of a bundle is simply its corresponding fully qualified PHP class name (without the leading backslash). An asset bundle class should be autoloadable. It usually specifies where the assets are located, what CSS and JavaScript files the bundle contains, and how the bundle depends on other bundles.

The following code defines the main asset bundle used:

```php
<?php

namespace App\Assets;

use Yiisoft\Asset\AssetBundle;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';

    public $baseUrl = '@web';

    public $css = [
        'css/site.css',
        ['css/print.css', 'media' => 'print'],
    ];

    public $js = [
    ];

    public $depends = [
        \Yiisoft\Yii\JQuery\YiiAsset::class,
        \Yiisoft\Bootstrap4\BootstrapAsset::class,
    ];
}
```
