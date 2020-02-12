<?php

namespace Yiisoft\View\Tests\Mocks;

class WebViewPlaceholderMock extends \Yiisoft\View\WebView
{
    public function endPage($ajaxMode = false): void
    {
        $this->setPlaceholderSalt("" . time());
        parent::endPage($ajaxMode);
    }
}
