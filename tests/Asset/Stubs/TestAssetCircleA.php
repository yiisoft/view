<?php

namespace Yiisoft\Asset\Tests\Stubs;

use Yiisoft\Asset\AssetBundle;

class TestAssetCircleA extends AssetBundle
{
    public $basePath = '@public/Js';

    public $baseUrl = '@web/Js';

    public $js = [
        'Jquery.js',
    ];

    public $depends = [
        TestAssetCircleB::class,
    ];
}
