<?php

namespace Yiisoft\Asset\Tests\Stubs;

use Yiisoft\Asset\AssetBundle;

class TestJqueryConflictAsset extends AssetBundle
{
    public $aliases;

    public $basePath = '@public/js';

    public $baseUrl = '@web/js';

    public $js = [
        'jquery.js',
    ];

    public $jsOptions = [
        'position' => 2
    ];

    public $depends = [
        TestAssetLevel3::class,
    ];
}
