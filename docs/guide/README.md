Assets
======

<p align="justify">An asset in Yii is a file that may be referenced in a Web page. It can be a CSS file, a JavaScript file, an image or video file, etc. Assets are located in Web Accessible directories and are directly served by Web servers.</p>

<p align="justify">It is often preferable to manage assets programmatically. For example, when you use the  [Yiisoft\Boostrap4\BoostrapAsset] widget in a page, it will automatically include the required CSS and JavaScript files, instead of asking you to manually find these files and include them. And when you upgrade the widget to a new version, it will automatically use the new version of the asset files. In this tutorial, we will describe the powerful asset management capability provided in Yii.</p>


## Asset Bundles <span id="asset-bundles"></span>

<p align="justify">Yii manages assets in the unit of *asset bundle*. An asset bundle is simply a collection of assets located in a directory. When you register an asset bundle, it will include the CSS and JavaScript files in the bundle in the rendered Web page.<p align="justify">


## Setting up your composer.json <span id="config-composer-json"></span>

<p align="justify">There are several ways to use the assets in Yii, you can use it in the traditional way in Yii2 by placing the Bower and NPM dependencies, in this case all packages are downloaded from: 

- [AssetPackagist](https://asset-packagist.org/).</p>

Directory structure:

- vendor
  - bower
  - npm

```    
{
    "name": "assets/assets-bootbox",
    "type": "library",
    "minimum-stability": "dev",
    "require": {
        "php": "^7.2.0",
        "npm-asset/bootbox": "@dev"
    },
    "config": {
        "process-timeout": 1800,
        "fxp-asset": {
            "enabled": false
        }
    },
    "repositories": [
        {
            "type": "composer",
            "url": "https://asset-packagist.org"
        }
    ]
}
```

<p align="justify">Another option is to move the assets of the vendor directory to the /node_modules directory, we can do it in two ways, the first one maintaining the dependence of <strong>AssetPackagist</strong> and the second way using <strong>foxy with hidev-composer-plugin</strong>, this will allow the assets to be downloaded from <strong>npm</strong>.</p>

Directory structure:

- node_modules:
  - bower
  - npm
- vendor:

```
download packages from AssetPackagist 

{
    "name": "assets/asset-bootbox",
    "type": "library",
    "minimum-stability": "dev",
    "prefer-stable": true,
    "require": {
        "php": "^7.2.0",
        "oomphinc/composer-installers-extender": "^1.1",
        "npm-asset/bootbox": "@dev"
    },
    "autoload": {
        "psr-4": {"assets\\asset-bootbox\\": "src/"}
    },
    "autoload-dev": {
        "psr-4": {"assets\\asset-bootbox\\tests\\": "tests/"}
	},
    "config": {
        "process-timeout": 1800,
        "fxp-asset": {
            "enabled": false
        }
    },
    "extra": {
        "installer-types": [
            "bower-asset",
            "npm-asset"
        ],
        "installer-paths": {
            "./node_modules/{$name}": [
                "type:bower-asset",
                "type:npm-asset"
            ]
        }
    },
    "repositories": [
        {
            "type": "composer",
            "url": "https://asset-packagist.org"
        }
    ]
}
```

<p align="justify">Now if we want the dependencies to be downloaded from <strong>NPM</strong> we configure it as follows:</p>

Directory structure:

- node_modules:
    - bower
    - npm
- vendor:

```
download packages from npm

{
    "name": "assets/asset-bootbox",
    "type": "library",
    "minimum-stability": "dev",
    "prefer-stable": true,
    "require": {
        "php": "^7.2.0",
		"foxy/foxy": "^1.0",
        "hiqdev/composer-config-plugin": "^1.0@dev",
    },
    "autoload": {
        "psr-4": {"assets\\asset-bootbox\\": "src/"}
    },
    "autoload-dev": {
        "psr-4": {"assets\\asset-bootbox\\tests\\": "tests/"}
	},
    "config": {
        "process-timeout": 1800,
        "fxp-asset": {
            "enabled": false
        }
    }
}

create package.json

{
    "name": "assets-asset-bootbox",
    "license": "BSD-3-Clause",
    "dependencies": {
        "bootbox": "^5.1.3"
    }
}

```

<p align="justify">Now when composing update --prefer-dist all your assets will be downloaded from <strong>NPM</strong>, obviously you must have NPM:

- [NPM](https://nodejs.org/en/download/).</p>

<p align="justify"><strong>Note: In both cases already the AssetManager by default handles the option of alternatives which by default is in '@npm/node_modules', you can move the assets anywhere whenever you configure the path.</strong></p>

## Defining Asset Bundles <span id="defining-asset-bundles"></span>

<p align="justify">Asset bundles are specified as PHP classes extending from [Yiisoft\Asset\AssetBundle]. The name of a bundle is simply its corresponding fully qualified PHP class name (without the leading backslash). An asset bundle class should be autoloadable. It usually specifies where the assets are located, what CSS and JavaScript files the bundle contains, and how the bundle depends on other bundles.</p>

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
