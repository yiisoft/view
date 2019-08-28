## Configuration: composer.json <span id="config-composer-json"></span>

There are several ways to use the assets in Yii, you can use it in the traditional way in Yii2 by placing the Bower and NPM dependencies, in this case all packages are downloaded from:

- [AssetPackagist](https://asset-packagist.org/).

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
    },
    "repositories": [
        {
            "type": "composer",
            "url": "https://asset-packagist.org"
        }
    ]
}
```

Another option is to move the assets of the vendor directory to the /node_modules directory, we can do it in two ways, the first one maintaining the dependence of **AssetPackagist** and the second way using **foxy with hidev-composer-plugin**, this will allow the assets to be downloaded from **npm**.

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

Now if we want the dependencies to be downloaded from **npm** we configure it as follows:

Directory structure:

```
node_modules/
  bower/
  npm/
vendor/
```

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
        "process-timeout": 1800
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

Now when doing `composer update --prefer-dist` all your assets will be downloaded from **npm** repository. In order for it to work, **npm** is required:

- [NPM](https://nodejs.org/en/download/).

> Note: By default the `AssetManager` uses `@npm/node_modules` directory in order to store downloaded packages. If you need to move the assets you can adjust the path.
