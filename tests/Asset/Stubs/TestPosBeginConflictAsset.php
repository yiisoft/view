<?php

namespace Yiisoft\Asset\Tests\Stubs;

use Yiisoft\Asset\AssetBundle;

class TestPosBeginConflictAsset extends AssetBundle
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
        'position' => 1
    ];

    public $depends = [
        TestJqueryConflictAsset::class,
    ];
}
