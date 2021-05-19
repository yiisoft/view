<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Yiisoft\Files\FileHelper;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\Tests\TestSupport\TestHelper;
use Yiisoft\View\Theme;
use Yiisoft\View\View;
use Yiisoft\View\ViewContextInterface;

use function is_array;

final class ViewTest extends TestCase
{
    protected string $tempDirectory;

    public function setUp(): void
    {
        parent::setUp();
        $this->tempDirectory = __DIR__ . '/public/tmp/View';
        FileHelper::ensureDirectory($this->tempDirectory);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        FileHelper::removeDirectory($this->tempDirectory);
    }

    /**
     * @link https://github.com/yiisoft/yii2/issues/13058
     */
    public function testExceptionOnRenderFile(): void
    {
        $view = $this->createViewWithBasePath($this->tempDirectory);

        $exceptionViewFile = $this->tempDirectory . DIRECTORY_SEPARATOR . 'exception.php';
        file_put_contents(
            $exceptionViewFile,
            <<<'PHP'
<h1>Exception</h1>
<?php throw new Exception('Test Exception'); ?>
PHP
        );
        $normalViewFile = $this->tempDirectory . DIRECTORY_SEPARATOR . 'no-exception.php';
        file_put_contents(
            $normalViewFile,
            <<<'PHP'
<h1>No Exception</h1>
PHP
        );

        $obInitialLevel = ob_get_level();

        try {
            $view->renderFile($exceptionViewFile);
        } catch (\Exception $e) {
            // shutdown exception
        }
        $view->renderFile($normalViewFile);

        $this->assertEquals($obInitialLevel, ob_get_level());
    }

    public function testRelativePathInView(): void
    {
        $themePath = $this->tempDirectory . '/theme1';
        FileHelper::ensureDirectory($themePath);

        $baseView = "{$this->tempDirectory}/theme1/base.php";
        file_put_contents(
            $baseView,
            <<<'PHP'
<?= $this->render("sub") ?>
PHP
        );

        $subView = "{$this->tempDirectory}/sub.php";
        $subViewContent = 'subviewcontent';
        file_put_contents($subView, $subViewContent);

        $view = $this->createViewWithBasePath($this->tempDirectory)
            ->withTheme(
                new Theme([
                    $this->tempDirectory => $themePath,
                ])
            );

        $this->assertSame($subViewContent, $view->render('//base'));
    }

    public function testRelativePathInViewWithContext(): void
    {
        $baseViewPath = $this->tempDirectory . '/test';
        FileHelper::ensureDirectory($baseViewPath);

        $baseView = "{$baseViewPath}/base.php";
        file_put_contents(
            $baseView,
            <<<'PHP'
<?= $this->render("sub/sub") ?>
PHP
        );

        $subViewPath = $baseViewPath . DIRECTORY_SEPARATOR . 'sub';
        FileHelper::ensureDirectory($subViewPath);

        $subView = "{$subViewPath}/sub.php";
        $subViewContent = 'subviewcontent';
        file_put_contents($subView, $subViewContent);

        $view = $this->createViewWithBasePath($this->tempDirectory)
            ->withContext($this->createContext($this->tempDirectory));

        $this->assertSame($subViewContent, $view->render('test/base'));
    }

    public function testLocalizedDirectory(): void
    {
        $view = $this->createViewWithBasePath($this->tempDirectory);
        $this->createFileStructure([
            'views' => [
                'faq.php' => 'English FAQ',
                'de-DE' => [
                    'faq.php' => 'German FAQ',
                ],
            ],
        ], $this->tempDirectory);
        $viewFile = $this->tempDirectory . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'faq.php';
        $sourceLanguage = 'en-US';

        // Source language and target language are same. The view path should be unchanged.
        $currentLanguage = $sourceLanguage;
        $this->assertSame($viewFile, $view->localize($viewFile, $currentLanguage, $sourceLanguage));

        // Source language and target language are different. The view path should be changed.
        $currentLanguage = 'de-DE';
        $this->assertSame(
            $this->tempDirectory . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $currentLanguage . DIRECTORY_SEPARATOR . 'faq.php',
            $view->localize($viewFile, $currentLanguage, $sourceLanguage)
        );
    }

    /**
     * Creates test files structure.
     *
     * @param string $baseDirectory base directory path.
     * @param array $items file system objects to be created in format: objectName => objectContent
     * Arrays specifies directories, other values - files.
     */
    private function createFileStructure(array $items, string $baseDirectory = null): void
    {
        foreach ($items as $name => $content) {
            $itemName = $baseDirectory . '/' . $name;
            if (is_array($content)) {
                if (isset($content[0], $content[1]) && $content[0] === 'symlink') {
                    symlink($baseDirectory . DIRECTORY_SEPARATOR . $content[1], $itemName);
                } else {
                    if (!mkdir($itemName, 0777, true) && !is_dir($itemName)) {
                        throw new RuntimeException(sprintf('Directory "%s" was not created', $itemName));
                    }
                    $this->createFileStructure($content, $itemName);
                }
            } else {
                file_put_contents($itemName, $content);
            }
        }
    }

    public function testDefaultParameterIsPassedToView(): void
    {
        $view = TestHelper::createView()
            ->withDefaultParameters(['parameter' => 'default_parameter']);

        $output = $view->render('//parameters');

        $this->assertSame('default_parameter', $output);
    }

    public function testDefaultParameterIsOverwrittenByLocalParameter(): void
    {
        $view = TestHelper::createView()
            ->withDefaultParameters(['parameter' => 'default_parameter']);

        $output = $view->render('//parameters', [
            'parameter' => 'local_parameter',
        ]);

        $this->assertSame('local_parameter', $output);
    }

    public function testPlaceholderSalt(): void
    {
        $view = TestHelper::createView();

        $view->setPlaceholderSalt('apple');

        $this->assertSame(
            dechex(crc32('apple')),
            $view->getPlaceholderSignature()
        );
    }

    private function createViewWithBasePath(string $basePath): View
    {
        return new View(
            $basePath,
            new SimpleEventDispatcher(),
            new NullLogger(),
        );
    }

    private function createContext(string $viewPath): ViewContextInterface
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
}
