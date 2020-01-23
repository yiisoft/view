<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use hiqdev\composer\config\Builder;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Di\Container;
use Yiisoft\Files\FileHelper;
use Yiisoft\View\Theme;
use Yiisoft\View\View;
use Yiisoft\View\WebView;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var Aliases $aliases
     */
    protected $aliases;

    /**
     * @var ContainerInterface $container
     */
    private $container;

    /**
     * @var EventDispatcherInterface $eventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var Theme $theme
     */
    protected $theme;

    /**
     * @var WebView $webView
     */
    protected $webView;

    /**
     * @var ListenerProviderInterface
     */
    protected $listenerProvider;

    /**
     * setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = require Builder::path('tests');

        $this->container = new Container($config);

        $this->aliases = $this->container->get(Aliases::class);
        $this->eventDispatcher = $this->container->get(EventDispatcherInterface::class);
        $this->listenerProvider = $this->container->get(ListenerProviderInterface::class);
        $this->logger = $this->container->get(LoggerInterface::class);
        $this->webView = $this->container->get(WebView::class);
    }

    /**
     * tearDown
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->container = null;
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
     * @param Theme  $theme
     *
     * @return View
     */
    protected function createView($basePath, Theme $theme = null): View
    {
        return new View($basePath, $theme ?: new Theme(), $this->eventDispatcher, $this->logger);
    }

    protected function touch(string $path): void
    {
        FileHelper::createDirectory(dirname($path));

        touch($path);
    }
}
