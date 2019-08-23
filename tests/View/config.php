<?php

use Yiisoft\Aliases\Aliases;

$tempDir = sys_get_temp_dir();

return [
    Aliases::class => [
        '@web' => '/baseUrl',
    ],
];
