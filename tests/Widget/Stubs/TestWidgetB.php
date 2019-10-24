<?php
declare(strict_types = 1);

namespace Yiisoft\Widget\Tests\Stubs;

use Psr\Log\LoggerInterface;
use Yiisoft\View\WebView;
use Yiisoft\Widget\Widget;

/**
 * TestWidgetB
 */
class TestWidgetB extends Widget
{
    /**
     * @var string $id;
     */
    private $id;

    /**
     * @param WebView $webView
     */
    private $webView;

    public function __construct(WebView $webView, LoggerInterface $logger)
    {
        parent::__construct($webView);
        $this->logger = $logger;
    }

    public function init(): void
    {
    }

    public function run(): string
    {
        return '<run-' . $this->id . '-construct>';
    }

    public function id(string $value): Widget
    {
        $this->id = $value;

        return $this;
    }
}
