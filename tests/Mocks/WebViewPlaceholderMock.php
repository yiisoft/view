<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Mocks;

use Yiisoft\View\WebView;

class WebViewPlaceholderMock extends WebView
{
    public function endPage($ajaxMode = false): void
    {
        $this->setPlaceholderSalt((string)time());
        parent::endPage($ajaxMode);
    }
}
