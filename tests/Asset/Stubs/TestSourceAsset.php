<?php

namespace Yiisoft\Asset\Tests\Stubs;

use Yiisoft\Asset\AssetBundle;

class TestSourceAsset extends AssetBundle
{
    public $sourcePath = '@testSourcePath';

    public $css = [
        'css/stub.css',
    ];

    public $js = [
        'js/stub.js',
    ];
}
