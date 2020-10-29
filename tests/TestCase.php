<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Di\Container;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Files\FileHelper;
use Yiisoft\View\Theme;
use Yiisoft\View\View;
use Yiisoft\View\WebView;
use Yiisoft\View\Tests\Mocks\WebViewPlaceholderMock;

use function str_replace;

abstract class TestCase extends BaseTestCase
{
    private ContainerInterface $container;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;
    protected Aliases $aliases;
    protected WebView $webView;
    protected WebViewPlaceholderMock $webViewPlaceholderMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container($this->config());

        $this->aliases = $this->container->get(Aliases::class);
        $this->eventDispatcher = $this->container->get(EventDispatcherInterface::class);
        $this->logger = $this->container->get(LoggerInterface::class);
        $this->webView = $this->container->get(WebView::class);
        $this->webViewPlaceholderMock = $this->container->get(WebViewPlaceholderMock::class);
    }

    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * tearDown
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->container, );
        parent::tearDown();
    }

    /**
     * Asserting two strings equality ignoring line endings.
     * @param string $expected
     * @param string $actual
     * @param string $message
     *
     * @return void
     */
    protected function assertEqualsWithoutLE(string $expected, string $actual, string $message = ''): void
    {
        $expected = str_replace("\r\n", "\n", $expected);
        $actual = str_replace("\r\n", "\n", $actual);

        $this->assertEquals($expected, $actual, $message);
    }

    /**
     * Asserting same ignoring slash.
     *
     * @param string $expected
     * @param string $actual
     *
     * @return void
     */
    protected function assertSameIgnoringSlash(string $expected, string $actual): void
    {
        $expected = str_replace(['/', '\\'], '/', $expected);
        $actual = str_replace(['/', '\\'], '/', $actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * Create view tests.
     *
     * @param string $basePath
     * @param Theme|null $theme
     *
     * @return View
     */
    protected function createView(string $basePath, ?Theme $theme = null): View
    {
        return new View($basePath, $theme ?: new Theme(), $this->eventDispatcher, $this->logger);
    }

    protected function touch(string $path): void
    {
        FileHelper::createDirectory(dirname($path));

        touch($path);
    }

    private function config(): array
    {
        return [
            Aliases::class => [
                '__class' => Aliases::class,
                '__construct()' => [
                    [
                        '@root' => __DIR__,
                        '@baseUrl' => '/baseUrl'
                    ]
                ]
            ],

            LoggerInterface::class => NullLogger::class,

            ListenerProviderInterface::class => Provider::class,

            EventDispatcherInterface::class => Dispatcher::class,

            View::class => [
                '__class' => View::class,
                '__construct()' => [
                    'basePath' => __DIR__ . '/public/view'
                ]
            ],

            WebView::class => [
                '__class' => WebView::class,
                '__construct()' => [
                    'basePath' => __DIR__ . '/public/view'
                ]
            ],

            WebViewPlaceholderMock::class => [
                '__class' => WebViewPlaceholderMock::class,
                '__construct()' => [
                    'basePath' => __DIR__ . '/public/view'
                ]
            ]
        ];
    }
}
