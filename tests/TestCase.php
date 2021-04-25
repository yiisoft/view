<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Log\NullLogger;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Files\FileHelper;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\Tests\Mocks\WebViewPlaceholderMock;
use Yiisoft\View\Theme;
use Yiisoft\View\View;
use Yiisoft\View\ViewContextInterface;
use Yiisoft\View\WebView;

use function str_replace;

abstract class TestCase extends BaseTestCase
{
    protected Aliases $aliases;
    protected WebView $webView;
    protected WebViewPlaceholderMock $webViewPlaceholderMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aliases = new Aliases([
            '@root' => __DIR__,
            '@baseUrl' => '/baseUrl',
        ]);

        $this->webView = new WebView(
            __DIR__ . '/public/view',
            new SimpleEventDispatcher(),
            new NullLogger()
        );

        $this->webViewPlaceholderMock = new WebViewPlaceholderMock(
            __DIR__ . '/public/view',
            new SimpleEventDispatcher(),
            new NullLogger()
        );
    }

    /**
     * Asserting two strings equality ignoring line endings.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
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
        $view = new View($basePath, new SimpleEventDispatcher(), new NullLogger());
        return $theme === null ? $view : $view->withTheme($theme);
    }

    protected function createContext(string $viewPath): ViewContextInterface
    {
        return new class($viewPath) implements ViewContextInterface {
            private string $viewPath;

            public function __construct(string $viewPath)
            {
                $this->viewPath = $viewPath;
            }

            public function getViewPath(): string
            {
                return $this->viewPath;
            }
        };
    }

    protected function touch(string $path): void
    {
        FileHelper::ensureDirectory(dirname($path));

        touch($path);
    }

    public function assertStringContainsStringIgnoringLineEndings(
        string $needle,
        string $haystack,
        string $message = ''
    ): void {
        $needle = self::normalizeLineEndings($needle);
        $haystack = self::normalizeLineEndings($haystack);

        $this->assertStringContainsString($needle, $haystack, $message);
    }

    private static function normalizeLineEndings(string $value): string
    {
        return strtr($value, [
            "\r\n" => "\n",
            "\r" => "\n",
        ]);
    }
}
