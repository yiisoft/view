<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\Theme;
use Yiisoft\View\View;
use Yiisoft\View\WebView;

final class ConfigTest extends TestCase
{
    public function testDi(): void
    {
        $container = $this->createContainer();

        $view = $container->get(View::class);

        $this->assertInstanceOf(View::class, $view);
    }

    public function testDiWeb(): void
    {
        $container = $this->createContainer('web');

        $theme = $container->get(Theme::class);
        $webView = $container->get(WebView::class);

        $this->assertInstanceOf(Theme::class, $theme);
        $this->assertInstanceOf(WebView::class, $webView);
    }

    private function createContainer(?string $postfix = null): Container
    {
        return new Container(
            ContainerConfig::create()->withDefinitions(
                $this->getDiConfig($postfix)
                +
                [
                    EventDispatcherInterface::class => new SimpleEventDispatcher(),
                ]
            )
        );
    }

    private function getDiConfig(?string $postfix = null): array
    {
        $params = $this->getParams();
        return require dirname(__DIR__) . '/config/di' . ($postfix !== null ? '-' . $postfix : '') . '.php';
    }

    private function getParams(): array
    {
        return require dirname(__DIR__) . '/config/params.php';
    }
}
