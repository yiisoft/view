<?php

namespace Yiisoft\Asset\Tests\Stubs;

use Yiisoft\Asset\AssetBundle;
use Yiisoft\View\WebView;

class TestPosEndAsset extends AssetBundle
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
        'position' => 3
    ];

    public $depends = [
        TestJqueryAsset::class,
    ];
}
