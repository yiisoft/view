<?php

namespace Yiisoft\Asset\Tests\Stubs;

use Yiisoft\Asset\AssetBundle;

class TestAssetPerFileOptions extends AssetBundle
{
    public $basePath = '@public';

    public $baseUrl = '@web';

    public $css = [
        'default_options.css',
        ['tv.css', 'media' => 'tv'],
        ['screen_and_print.css', 'media' => 'screen, print'],
    ];

    public $js = [
        'normal.js',
        ['defered.js', 'defer' => true],
    ];

    public $cssOptions = ['media' => 'screen', 'hreflang' => 'en'];
    public $jsOptions = ['charset' => 'utf-8'];
}
