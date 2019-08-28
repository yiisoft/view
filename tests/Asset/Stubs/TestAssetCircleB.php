<?php

namespace Yiisoft\Asset\Tests\Stubs;

use Yiisoft\Asset\AssetBundle;

class TestAssetCircleB extends AssetBundle
{
    public $basePath = '@public/js';

    public $baseUrl = '@web/js';

    public $js = [
        'jquery.js',
    ];

    public $depends = [
        TestAssetCircleA::class,
    ];
}
