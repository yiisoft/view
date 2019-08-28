<?php

namespace Yiisoft\Asset\Tests\Stubs;

use Yiisoft\Asset\AssetBundle;

class TestPosBeginAsset extends AssetBundle
{
    public $basePath = '@public/files';

    public $baseUrl = '@web/files';

    public $css = [
        'cssFile.css',
    ];

    public $js = [
        'jsFile.js',
    ];

    public $jsOptions = [
        'position' => 2
    ];

    public $depends = [
        TestJqueryAsset::class,
    ];
}
