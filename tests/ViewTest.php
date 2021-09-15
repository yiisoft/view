<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Files\FileHelper;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\Event\View\PageBegin;
use Yiisoft\View\Event\View\PageEnd;
use Yiisoft\View\Exception\ViewNotFoundException;
use Yiisoft\View\PhpTemplateRenderer;
use Yiisoft\View\Tests\TestSupport\TestHelper;
use Yiisoft\View\Tests\TestSupport\TestTrait;
use Yiisoft\View\Theme;
use Yiisoft\View\View;
use Yiisoft\View\ViewContextInterface;

use function crc32;
use function dechex;
use function file_put_contents;
use function is_array;
use function is_dir;
use function mkdir;
use function ob_get_level;
use function sprintf;
use function symlink;

final class ViewTest extends TestCase
{
    use TestTrait;

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
        } catch (Exception $e) {
            // shutdown exception
        }
        $view->renderFile($normalViewFile);

        $this->assertEquals($obInitialLevel, ob_get_level());
    }

    public function testExceptionWhenRenderIfFileNotExists(): void
    {
        $view = $this->createViewWithBasePath($this->tempDirectory);

        $this->expectException(ViewNotFoundException::class);
        $this->expectExceptionMessage('The view file "not-exist.php" does not exist.');

        $view->renderFile('not-exist.php');
    }

    public function testExceptionWhenRenderIfNoActiveViewContext(): void
    {
        $view = $this->createViewWithBasePath($this->tempDirectory);
        file_put_contents("$this->tempDirectory/file.php", 'Test');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve view file for view "file.php": no active view context.');

        $this->assertSame('Test', $view->render('file.php'));
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

        $theme = new Theme([$this->tempDirectory => $themePath]);
        $view = $this->createViewWithBasePath($this->tempDirectory)->withTheme($theme);

        $this->assertSame($this->tempDirectory, $view->getBasePath());
        $this->assertSame('php', $view->getDefaultExtension());
        $this->assertSame(null, $view->getViewFile());
        $this->assertSame($theme, $view->getTheme());
        $this->assertSame($subViewContent, $view->render('/base'));
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

        $this->assertSame(null, $view->getTheme());
        $this->assertSame($subViewContent, $view->render('test/base'));
    }

    public function testRenderWithoutFileExtension(): void
    {
        $view = $this->createViewWithBasePath($this->tempDirectory)
            ->withContext($this->createContext($this->tempDirectory))
        ;
        file_put_contents("$this->tempDirectory/file.php", 'Test');
        file_put_contents("$this->tempDirectory/file.tpl", 'Test');
        file_put_contents("$this->tempDirectory/file.txt.php", 'Test');

        $this->assertSame('Test', $view->render('file'));
        $this->assertSame('Test', $view->withDefaultExtension('tpl')->render('file'));
        $this->assertSame('Test', $view->withDefaultExtension('txt')->render('file'));
    }

    public function testLocalize(): void
    {
        $view = $this->createViewWithBasePath($this->tempDirectory);

        FileHelper::ensureDirectory("$this->tempDirectory/es");
        file_put_contents("$this->tempDirectory/es/file.php", 'Prueba');

        FileHelper::ensureDirectory("$this->tempDirectory/ru-RU");
        file_put_contents("$this->tempDirectory/ru-RU/file.php", 'Тест');

        $this->assertSameIgnoringSlash(
            "$this->tempDirectory/file.php",
            $view->localize("$this->tempDirectory/file.php"),
        );

        $this->assertSameIgnoringSlash(
            "$this->tempDirectory/es/file.php",
            $view->localize("$this->tempDirectory/file.php", 'es'),
        );

        $this->assertSameIgnoringSlash(
            "$this->tempDirectory/es/file.php",
            $view->localize("$this->tempDirectory/file.php", 'es-ES', 'ru-RU'),
        );

        $this->assertSameIgnoringSlash(
            "$this->tempDirectory/file.php",
            $view->localize("$this->tempDirectory/file.php", 'es-ES', 'es'),
        );

        $this->assertSameIgnoringSlash(
            "$this->tempDirectory/file.php",
            $view->localize("$this->tempDirectory/file.php", 'ru'),
        );
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

    public function testParameter(): void
    {
        $view = TestHelper::createView();
        $this->assertFalse($view->hasParameter('id'));

        $view->setParameters(['id' => 0]);
        $this->assertTrue($view->hasParameter('id'));
        $this->assertSame(0, $view->getParameter('id'));

        $view->setParameter('id', 42);
        $this->assertTrue($view->hasParameter('id'));
        $this->assertSame(42, $view->getParameter('id'));

        $view->removeParameter('id');
        $this->assertFalse($view->hasParameter('id'));

        $this->expectException(InvalidArgumentException::class);
        $view->getParameter('id');
    }

    public function testParameterDefaultValue(): void
    {
        $view = TestHelper::createView();

        $this->assertSame(42, $view->getParameter('id', 42));
    }

    public function testParameterIsPassedToView(): void
    {
        $view = TestHelper::createView();
        $view->setParameter('parameter', 'global-parameter');
        $output = $view->render('//parameters');

        $this->assertSame('global-parameter', $output);
    }

    public function testViewParameterIsOverwrittenByRenderParameter(): void
    {
        $view = TestHelper::createView();
        $view->setParameter('parameter', 'global-parameter');

        $output = $view->render('//parameters', [
            'parameter' => 'local-parameter',
        ]);

        $this->assertSame('local-parameter', $output);
    }

    public function testBlock(): void
    {
        $view = TestHelper::createView();
        $this->assertFalse($view->hasBlock('id'));

        $view->setBlock('id', 'content');
        $this->assertTrue($view->hasBlock('id'));
        $this->assertSame('content', $view->getBlock('id'));

        $view->removeBlock('id');
        $this->assertFalse($view->hasBlock('id'));

        $this->expectException(InvalidArgumentException::class);
        $view->getBlock('id');
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

    public function testPageEvents(): void
    {
        $eventDispatcher = new SimpleEventDispatcher();
        $view = TestHelper::createView($eventDispatcher);

        $view->beginPage();
        $view->endPage();

        $this->assertSame([
            PageBegin::class,
            PageEnd::class,
        ], $eventDispatcher->getEventClasses());
    }

    public function testImmutability(): void
    {
        $view = TestHelper::createView();

        $this->assertNotSame($view, $view->withTheme(new Theme([$this->tempDirectory => $this->tempDirectory])));
        $this->assertNotSame($view, $view->withRenderers([new PhpTemplateRenderer()]));
        $this->assertNotSame($view, $view->withLanguage('en'));
        $this->assertNotSame($view, $view->withSourceLanguage('en'));
        $this->assertNotSame($view, $view->withDefaultExtension('php'));
        $this->assertNotSame($view, $view->withContext($this->createContext($this->tempDirectory)));
    }

    private function createViewWithBasePath(string $basePath): View
    {
        return new View($basePath, new SimpleEventDispatcher());
    }

    private function createContext(string $viewPath): ViewContextInterface
    {
        return new class ($viewPath) implements ViewContextInterface {
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

    /**
     * Creates test files structure.
     *
     * @param string|null $baseDirectory base directory path.
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
}
