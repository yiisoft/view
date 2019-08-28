<?php

namespace Yiisoft\Asset\Tests\Stubs;

use Yiisoft\Asset\AssetBundle;

class TestJqueryAsset extends AssetBundle
{
    public $aliases;

    public $basePath = '@public/js';

    public $baseUrl = '@web/js';

    public $js = [
        'jquery.js',
    ];

    public $depends = [
        TestAssetLevel3::class,
    ];
}
