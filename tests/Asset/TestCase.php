<?php
declare(strict_types = 1);

namespace Yiisoft\Asset\Tests;

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
        $config = require __DIR__ . '/config.php';

        $this->container = new Container($config);
        $this->aliases = $this->container->get(Aliases::class);

        $this->assetManager = $this->container->get(AssetManager::class);

        $assetBundle = $this->container->get(AssetBundle::class);
        $eventDispatcher = $this->container->get(EventDispatcherInterface::class);
        $logger = $this->container->get(LoggerInterface::class);
        $theme = $this->container->get(Theme::class);
        $view = $this->aliases->get('@view');

        $this->webView = new WebView($view, $theme, $eventDispatcher, $logger);
        $this->webView->setAssetManager($this->assetManager);
        $this->removeAssets();
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

    public function removeAssets()
    {
        $handle = opendir($dir = $this->aliases->get('@basePath'));

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
     * sourcesPublishVerifyFiles
     *
     * @param [type] $type
     * @param [type] $bundle
     *
     * @return void
     */
    public function sourcesPublishVerifyFiles($type, $bundle)
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
    public function unlink($file)
    {
        if (is_dir($file) && DIRECTORY_SEPARATOR === '\\') {
            return rmdir($file);
        }

        return unlink($file);
    }

    /**
     * Asserting two strings equality ignoring line endings.
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
}
