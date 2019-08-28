<?php
declare(strict_types = 1);

namespace Yiisoft\Asset\Tests;

use Yiisoft\Asset\AssetBundle;
use Yiisoft\Asset\Tests\Stubs\TestAssetLevel3;
use Yiisoft\Asset\Tests\Stubs\TestAssetCircleA;
use Yiisoft\Asset\Tests\Stubs\TestPosBeginAsset;
use Yiisoft\Asset\Tests\Stubs\TestPosBeginConflictAsset;
use Yiisoft\Asset\Tests\Stubs\TestPosEndAsset;
use Yiisoft\Asset\Tests\Stubs\TestPosHeadAsset;
use Yiisoft\Asset\Tests\Stubs\TestJqueryAsset;
use Yiisoft\Asset\Tests\Stubs\TestJqueryConflictAsset;
use Yiisoft\Asset\Tests\Stubs\TestAssetPerFileOptions;
use Yiisoft\Asset\Tests\Stubs\TestSimpleAsset;
use Yiisoft\Asset\Tests\Stubs\TestSourceAsset;
use Yiisoft\Files\FileHelper;
use Yiisoft\Tests\TestCase;
use Yiisoft\View\WebView;

/**
 * AssetBundleTest.
 */
final class AssetBundleTest extends TestCase
{
    public function testCircularDependency(): void
    {
        $this->expectException(\RuntimeException::class);
        TestAssetCircleA::register($this->webView);
    }

    public function testDuplicateAssetFile(): void
    {
        $view = $this->webView;

        $this->assertEmpty($view->getAssetBundles());

        TestSimpleAsset::register($view);

        $this->assertCount(1, $view->getAssetBundles());
        $this->assertArrayHasKey(TestSimpleAsset::class, $view->getAssetBundles());
        $this->assertInstanceOf(AssetBundle::class, $view->getAssetBundles()[TestSimpleAsset::class]);
        // register TestJqueryAsset which also has the jquery.js

        TestJqueryAsset::register($view);

        $expected = <<<'EOF'
123<script src="/baseUrl/js/jquery.js"></script>4
EOF;
        $this->assertEquals($expected, $view->renderFile($this->aliases->get('@view/rawlayout.php')));
    }

    public function testPerFileOptions(): void
    {
        $view = $this->webView;

        $this->assertEmpty($view->getAssetBundles());

        TestAssetPerFileOptions::register($view);

        $expected = <<<'EOF'
1<link href="/baseUrl/default_options.css" rel="stylesheet" media="screen" hreflang="en">
<link href="/baseUrl/tv.css" rel="stylesheet" media="tv" hreflang="en">
<link href="/baseUrl/screen_and_print.css" rel="stylesheet" media="screen, print" hreflang="en">23<script src="/baseUrl/normal.js" charset="utf-8"></script>
<script src="/baseUrl/defered.js" charset="utf-8" defer></script>4
EOF;
        $this->assertEqualsWithoutLE($expected, $view->renderFile($this->aliases->get('@view/rawlayout.php')));
    }

    public function positionProvider(): array
    {
        return [
            [TestPosHeadAsset::class, WebView::POS_HEAD, true],
            [TestPosHeadAsset::class, WebView::POS_HEAD, false],
            [TestPosBeginAsset::class, WebView::POS_BEGIN, true],
            [TestPosBeginAsset::class, WebView::POS_BEGIN, false],
            [TestPosEndAsset::class, WebView::POS_END, true],
            [TestPosEndAsset::class, WebView::POS_END, false],
        ];
    }

    /**
     * @dataProvider positionProvider
     *
     * @param string $assetBundle
     * @param int $pos
     * @param bool $jqAlreadyRegistered
     */
    public function testPositionDependencyPos(string $assetBundle, int $pos, bool $jqAlreadyRegistered): void
    {
        $view = $this->webView;

        $this->assertEmpty($view->getAssetBundles());

        if ($jqAlreadyRegistered) {
            TestJqueryAsset::register($view);
        }

        $assetBundle::register($view);

        $this->assertCount(3, $view->getAssetBundles());

        $this->assertArrayHasKey($assetBundle, $view->getAssetBundles());
        $this->assertArrayHasKey(TestJqueryAsset::class, $view->getAssetBundles());
        $this->assertArrayHasKey(TestAssetLevel3::class, $view->getAssetBundles());

        $this->assertInstanceOf(AssetBundle::class, $view->getAssetBundles()[$assetBundle]);
        $this->assertInstanceOf(AssetBundle::class, $view->getAssetBundles()[TestJqueryAsset::class]);
        $this->assertInstanceOf(AssetBundle::class, $view->getAssetBundles()[TestAssetLevel3::class]);

        $this->assertArrayHasKey('position', $view->getAssetBundles()[$assetBundle]->jsOptions);
        $this->assertEquals($pos, $view->getAssetBundles()[$assetBundle]->jsOptions['position']);
        $this->assertArrayHasKey('position', $view->getAssetBundles()[TestJqueryAsset::class]->jsOptions);
        $this->assertEquals($pos, $view->getAssetBundles()[TestJqueryAsset::class]->jsOptions['position']);
        $this->assertArrayHasKey('position', $view->getAssetBundles()[TestAssetLevel3::class]->jsOptions);
        $this->assertEquals($pos, $view->getAssetBundles()[TestAssetLevel3::class]->jsOptions['position']);

        switch ($pos) {
            case WebView::POS_HEAD:
                $expected = <<<'EOF'
1<link href="/baseUrl/files/cssFile.css" rel="stylesheet">
<script src="/baseUrl/js/jquery.js"></script>
<script src="/baseUrl/files/jsFile.js"></script>234
EOF;
                break;
            case WebView::POS_BEGIN:
                $expected = <<<'EOF'
1<link href="/baseUrl/files/cssFile.css" rel="stylesheet">2<script src="/baseUrl/js/jquery.js"></script>
<script src="/baseUrl/files/jsFile.js"></script>34
EOF;
                break;
            default:
            case WebView::POS_END:
                $expected = <<<'EOF'
1<link href="/baseUrl/files/cssFile.css" rel="stylesheet">23<script src="/baseUrl/js/jquery.js"></script>
<script src="/baseUrl/files/jsFile.js"></script>4
EOF;
                break;
        }
        $this->assertEqualsWithoutLE($expected, $view->renderFile($this->aliases->get('@view/rawlayout.php')));
    }

    public function positionProvider2(): array
    {
        return [
            [TestPosBeginConflictAsset::class, WebView::POS_BEGIN, true],
            [TestPosBeginConflictAsset::class, WebView::POS_BEGIN, false],
        ];
    }

    /**
     * @dataProvider positionProvider2
     *
     * @param string $assetBundle
     * @param int  $pos
     * @param bool $jqAlreadyRegistered
     */
    public function testPositionDependencyConflict(string $assetBundle, int $pos, bool $jqAlreadyRegistered): void
    {
        $view = $this->webView;

        $this->assertEmpty($view->getAssetBundles());

        if ($jqAlreadyRegistered) {
            TestJqueryConflictAsset::register($view);
        }

        $this->expectException(\RuntimeException::class);
        $assetBundle::register($this->webView);
    }

    public function testSourcesPublishedBySymlinkIssue9333(): void
    {
        $this->assetManager->setLinkAssets(true);
        $this->assetManager->setHashCallback(
            function ($path) {
                return sprintf('%x/%x', crc32($path), crc32('3.0-dev'));
            }
        );
        $bundle = $this->verifySourcesPublishedBySymlink($this->webView);
        $this->assertTrue(is_dir(dirname($bundle->basePath)));
    }

    public function testSourcesPublishOptionsOnly(): void
    {
        $am = $this->webView->getAssetManager();
        $am->setLinkAssets(false);

        $bundle = new TestSourceAsset();

        $bundle->publishOptions = [
            'only' => [
                'js/*'
            ],
        ];

        $bundle->publish($am);

        $notNeededFilesDir = dirname($bundle->basePath . DIRECTORY_SEPARATOR . $bundle->css[0]);

        $this->assertFileNotExists($notNeededFilesDir);

        foreach ($bundle->js as $filename) {
            $publishedFile = $bundle->basePath . DIRECTORY_SEPARATOR . $filename;
            $this->assertFileExists($publishedFile);
        }

        $this->assertTrue(is_dir(dirname($bundle->basePath . DIRECTORY_SEPARATOR . $bundle->js[0])));
        $this->assertTrue(is_dir($bundle->basePath));
    }

    public function registerFileDataProvider(): array
    {
        return [
            // Custom alias repeats in the asset URL
            [
                'css', '@web/assetSources/repeat/css/stub.css', false,
                '1<link href="/repeat/assetSources/repeat/css/stub.css" rel="stylesheet">234',
                '/repeat',
            ],
            [
                'js', '@web/assetSources/repeat/js/jquery.js', false,
                '123<script src="/repeat/assetSources/repeat/js/jquery.js"></script>4',
                '/repeat',
            ],

            // JS files registration
            [
                'js', '@web/assetSources/js/missing-file.js', true,
                '123<script src="/baseUrl/assetSources/js/missing-file.js"></script>4',
            ],
            [
                'js', '@web/assetSources/js/jquery.js', false,
                '123<script src="/baseUrl/assetSources/js/jquery.js"></script>4',
            ],
            [
                'js', 'http://example.com/assetSources/js/jquery.js', false,
                '123<script src="http://example.com/assetSources/js/jquery.js"></script>4',
            ],
            [
                'js', '//example.com/assetSources/js/jquery.js', false,
                '123<script src="//example.com/assetSources/js/jquery.js"></script>4',
            ],
            [
                'js', 'assetSources/js/jquery.js', false,
                '123<script src="assetSources/js/jquery.js"></script>4',
            ],
            [
                'js', '/assetSources/js/jquery.js', false,
                '123<script src="/assetSources/js/jquery.js"></script>4',
            ],

            // CSS file registration
            [
                'css', '@web/assetSources/css/missing-file.css', true,
                '1<link href="/baseUrl/assetSources/css/missing-file.css" rel="stylesheet">234',
            ],
            [
                'css', '@web/assetSources/css/stub.css', false,
                '1<link href="/baseUrl/assetSources/css/stub.css" rel="stylesheet">234',
            ],
            [
                'css', 'http://example.com/assetSources/css/stub.css', false,
                '1<link href="http://example.com/assetSources/css/stub.css" rel="stylesheet">234',
            ],
            [
                'css', '//example.com/assetSources/css/stub.css', false,
                '1<link href="//example.com/assetSources/css/stub.css" rel="stylesheet">234',
            ],
            [
                'css', 'assetSources/css/stub.css', false,
                '1<link href="assetSources/css/stub.css" rel="stylesheet">234',
            ],
            [
                'css', '/assetSources/css/stub.css', false,
                '1<link href="/assetSources/css/stub.css" rel="stylesheet">234',
            ],

            // Custom `@web` aliases
            [
                'js', '@web/assetSources/js/missing-file1.js', true,
                '123<script src="/backend/assetSources/js/missing-file1.js"></script>4',
                '/backend',
            ],
            [
                'js', 'http://full-url.example.com/backend/assetSources/js/missing-file.js', true,
                '123<script src="http://full-url.example.com/backend/assetSources/js/missing-file.js"></script>4',
                '/backend',
            ],
            [
                'css', '//backend/backend/assetSources/js/missing-file.js', true,
                '1<link href="//backend/backend/assetSources/js/missing-file.js" rel="stylesheet">234',
                '/backend',
            ],
            [
                'css', '@web/assetSources/css/stub.css', false,
                '1<link href="/en/blog/backend/assetSources/css/stub.css" rel="stylesheet">234',
                '/en/blog/backend',
            ],

            // UTF-8 chars
            [
                'css', '@web/assetSources/css/stub.css', false,
                '1<link href="/рус/сайт/assetSources/css/stub.css" rel="stylesheet">234',
                '/рус/сайт',
            ],
            [
                'js', '@web/assetSources/js/jquery.js', false,
                '123<script src="/汉语/漢語/assetSources/js/jquery.js"></script>4',
                '/汉语/漢語',
            ],
        ];
    }

    /**
     * @dataProvider registerFileDataProvider
     *
     * @param string      $type            either `js` or `css`
     * @param string      $path
     * @param string|bool $appendTimestamp
     * @param string      $expected
     * @param string|null $webAlias
     */
    public function testRegisterFileAppendTimestamp($type, $path, $appendTimestamp, $expected, $webAlias = null): void
    {
        $originalAlias = $this->aliases->get('@web');

        if ($webAlias === null) {
            $webAlias = $originalAlias;
        }

        $this->aliases->set('@web', $webAlias);

        $path = $this->aliases->get($path);
        $view = $this->webView;
        $am = $this->webView->getAssetManager();
        $am->setAppendTimestamp($appendTimestamp);

        $method = 'register' . ucfirst($type) . 'File';

        $view->$method($path);

        $this->assertEquals($expected, $view->renderFile($this->aliases->get('@view/rawlayout.php')));
    }

    /**
     * @param WebView $view
     *
     * @return AssetBundle
     */
    public function verifySourcesPublishedBySymlink($view): AssetBundle
    {
        $am = $view->getAssetManager();

        $bundle = TestSourceAsset::register($view);
        $bundle->publish($am);

        $this->assertDirectoryExists($bundle->basePath);

        foreach ($bundle->js as $filename) {
            $publishedFile = $bundle->basePath . DIRECTORY_SEPARATOR . $filename;
            $sourceFile = $bundle->basePath . DIRECTORY_SEPARATOR . $filename;

            $this->assertTrue(is_link($bundle->basePath));
            $this->assertFileExists($publishedFile);
            $this->assertFileEquals($publishedFile, $sourceFile);
        }

        $this->assertTrue(FileHelper::unlink($bundle->basePath));

        return $bundle;
    }
}
