<?php

declare(strict_types=1);

use Yiisoft\View\Tests\TestSupport\CustomContext;

/**
 * @var $this \Yiisoft\View\View
 */

echo $this
    ->withContext(new CustomContext())
    ->render('view');
