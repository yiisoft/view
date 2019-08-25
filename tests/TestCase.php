<?php
declare(strict_types = 1);

namespace Yiisoft\Tests;

use hiqdev\composer\config\Builder;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Asset\AssetBundle;
use Yiisoft\Asset\AssetManager;
use Yiisoft\Files\FileHelper;
use Yiisoft\Di\Container;
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
     * @var AssetManager $assetManager
     */
    protected $assetManager;

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
    protected $webview;

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
        $this->assetManager = $this->container->get(AssetManager::class);
        $this->eventDispatcher = $this->container->get(EventDispatcherInterface::class);
        $this->logger = $this->container->get(LoggerInterface::class);
        $this->theme = $this->container->get(Theme::class);
        $this->webView = $this->createWebView($this->aliases->get('@view'));
        $this->webView->setAssetManager($this->assetManager);

        $this->removeAssets('@basePath');
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
     * @param string basePath
     * @param Theme $theme
     *
     * @return View
     */
    protected function createView($basePath, Theme $theme = null): View
    {
        return new View($basePath, $theme ?: new Theme(), $this->eventDispatcher, $this->logger);
    }

    /**
     * Create webview tests.
     *
     * @param string $basePath
     * @param Theme $theme
     *
     * @return View
     */
    protected function createWebView(string $basePath): WebView
    {
        return new WebView($basePath, $this->theme, $this->eventDispatcher, $this->logger);
    }

    public function touch(string $path): void
    {
        FileHelper::createDirectory(dirname($path));

        touch($path);
    }

    protected function removeAssets(string $basePath): void
    {
        $handle = opendir($dir = $this->aliases->get($basePath));

        if ($handle === false) {
            throw new \Exception("Unable to open directory: $dir");
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..' || $file === '.gitignore') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            if (is_dir($path)) {
                FileHelper::removeDirectory($path);
            } else {
                FileHelper::unlink($path);
            }
        }

        closedir($handle);
    }

    /**
     * Verify sources publish files assetbundle.
     *
     * @param string $type
     * @param AssetBundle $bundle
     *
     * @return void
     */
    protected function sourcesPublishVerifyFiles(string $type, AssetBundle $bundle): void
    {
        foreach ($bundle->$type as $filename) {
            $publishedFile = $bundle->basePath . DIRECTORY_SEPARATOR . $filename;
            $sourceFile = $this->aliases->get($bundle->sourcePath) . DIRECTORY_SEPARATOR . $filename;

            $this->assertFileExists($publishedFile);
            $this->assertFileEquals($publishedFile, $sourceFile);
        }

        $this->assertTrue(is_dir($bundle->basePath . DIRECTORY_SEPARATOR . $type));
    }

    /**
     * Properly removes symlinked directory under Windows, MacOS and Linux.
     *
     * @param string $file path to symlink
     *
     * @return bool
     */
    protected function unlink(string $file): bool
    {
        if (is_dir($file) && DIRECTORY_SEPARATOR === '\\') {
            return rmdir($file);
        }

        return unlink($file);
    }
}
