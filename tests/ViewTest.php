<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use Exception;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testAbsolutePath(): void
    {
        $view = new View();

        $result = $view->render(__DIR__ . '/public/view/parameters.php', ['parameter' => 42]);

        $this->assertSame('42', $result);
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
            $view->render($exceptionViewFile);
        } catch (Exception) {
            // shutdown exception
        }
        $view->render($normalViewFile);

        $this->assertEquals($obInitialLevel, ob_get_level());
    }

    public function testExceptionWhenRenderIfFileNotExists(): void
    {
        $view = $this->createViewWithBasePath($this->tempDirectory);

        $this->expectException(ViewNotFoundException::class);
        $this->expectExceptionMessage(
            'The view file "' .
            __DIR__ . '/public/tmp/View/not-exist.php' .
            '" does not exist.'
        );

        $view->render('not-exist.php');
    }

    public function testRelativePathInView(): void
    {
        $themePath = $this->tempDirectory . '/theme1';
        FileHelper::ensureDirectory($themePath);

        $baseView = "{$this->tempDirectory}/theme1/base.php";
        file_put_contents(
            $baseView,
            <<<'PHP'
            <?= $this->render('./sub') ?>
            PHP
        );

        $subView = "{$this->tempDirectory}/sub.php";
        $subViewContent = 'subviewcontent';
        file_put_contents($subView, $subViewContent);

        $theme = new Theme([$this->tempDirectory => $themePath]);
        $view = $this
            ->createViewWithBasePath($this->tempDirectory)
            ->setTheme($theme);

        $this->assertSame($this->tempDirectory, $view->getBasePath());
        $this->assertNull($view->getViewFile());
        $this->assertSame($theme, $view->getTheme());
        $this->assertSame($subViewContent, $view->render('base'));
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
            <?= $this->render('./sub/sub') ?>
            PHP
        );

        $subViewPath = $baseViewPath . DIRECTORY_SEPARATOR . 'sub';
        FileHelper::ensureDirectory($subViewPath);

        $subView = "{$subViewPath}/sub.php";
        $subViewContent = 'subviewcontent';
        file_put_contents($subView, $subViewContent);

        $view = $this
            ->createViewWithBasePath($this->tempDirectory)
            ->withContext($this->createContext($this->tempDirectory));

        $this->assertSame(null, $view->getTheme());
        $this->assertSame($subViewContent, $view->render('test/base'));
    }

    public function testWithContextPath(): void
    {
        $view = TestHelper::createView()->withContextPath(
            __DIR__ . '/public/view/custom-context'
        );

        $this->assertSame('42', $view->render('view'));
    }

    public function testFlushViewFilesOnChangeContext(): void
    {
        $view = TestHelper::createView();

        $this->assertSame('42', $view->render('change-context'));
    }

    public static function renderFilesWithExtensionProvider(): array
    {
        return [
            [
                'file',
                'php',
                'php',
                ['php'],
            ],
            [
                'file',
                'tpl',
                'tpl',
                ['tpl'],
            ],
            [
                'file',
                'phpt',
                'phpt',
                ['phpt'],
            ],
            [
                'file.txt',
                'twig',
                'twig',
                ['txt', 'twig'],
            ],
            [
                'file',
                'smarty',
                'smarty',
                ['smarty', 'twig'],
            ],
        ];
    }

    #[DataProvider('renderFilesWithExtensionProvider')]
    public function testRenderWithoutFileExtension(string $filename, string $extension, string $defaultExtension, array $fallbackExtensions): void
    {
        $view = $this
            ->createViewWithBasePath($this->tempDirectory)
            ->withContext($this->createContext($this->tempDirectory));
        file_put_contents("$this->tempDirectory/$filename.$extension", 'Test ' . $extension);

        $this->assertSame(
            'Test ' . $extension,
            $view->withFallbackExtension(...$fallbackExtensions)->render($filename)
        );
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

    public function testRenderSetLocale(): void
    {
        FileHelper::ensureDirectory($this->tempDirectory);
        FileHelper::ensureDirectory("$this->tempDirectory/es");
        file_put_contents("$this->tempDirectory/file.php", 'test en render');
        file_put_contents("$this->tempDirectory/es/file.php", 'test es render');

        $view = $this->createViewWithBasePath($this->tempDirectory);
        $view->setLocale('es');

        $this->assertSameIgnoringSlash(
            'test es render',
            $view->render('file'),
        );
    }

    public function testRenderWithLocale(): void
    {
        FileHelper::ensureDirectory($this->tempDirectory);
        FileHelper::ensureDirectory("$this->tempDirectory/es");
        file_put_contents("$this->tempDirectory/file.php", 'test en render');
        file_put_contents("$this->tempDirectory/es/file.php", 'test es render');

        $view = $this->createViewWithBasePath($this->tempDirectory);

        $this->assertSameIgnoringSlash(
            'test es render',
            $view->withLocale('es')->render('file'),
        );
        $this->assertSame('test en render', $view->render('file'));
    }

    public function testSubRenderWithLocale(): void
    {
        FileHelper::ensureDirectory($this->tempDirectory);
        FileHelper::ensureDirectory("$this->tempDirectory/es");
        file_put_contents("$this->tempDirectory/file.php", "<?php\n echo \$this->render('_sub-file');");
        file_put_contents("$this->tempDirectory/_sub-file.php", 'test en sub render');

        file_put_contents("$this->tempDirectory/es/file.php", "<?php\n echo \$this->render('_sub-file');");
        file_put_contents("$this->tempDirectory/es/_sub-file.php", 'test es sub render');

        $view = $this->createViewWithBasePath($this->tempDirectory);

        $this->assertSameIgnoringSlash(
            'test es sub render',
            $view->withLocale('es')->render('file'),
        );
        $this->assertSame('test en sub render', $view->render('file'));
    }

    public function testLocalizeWithChangedLocale(): void
    {
        FileHelper::ensureDirectory("$this->tempDirectory/es");
        file_put_contents("$this->tempDirectory/es/file.php", 'Prueba');

        $view = $this
            ->createViewWithBasePath($this->tempDirectory)
            ->setLocale('es');

        $this->assertSameIgnoringSlash(
            "$this->tempDirectory/es/file.php",
            $view->localize("$this->tempDirectory/file.php"),
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
        $sourceLocale = 'en-US';

        // Source locale and target locale are same. The view path should be unchanged.
        $currentLocale = $sourceLocale;
        $this->assertSame($viewFile, $view->localize($viewFile, $currentLocale, $sourceLocale));

        // Source locale and target locale are different. The view path should be changed.
        $currentLocale = 'de-DE';
        $this->assertSame(
            $this->tempDirectory . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $currentLocale . DIRECTORY_SEPARATOR . 'faq.php',
            $view->localize($viewFile, $currentLocale, $sourceLocale)
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

    public function testAddToParameter(): void
    {
        $view = TestHelper::createView();

        $view->addToParameter('test', 'a');

        $this->assertSame(['a'], $view->getParameter('test'));
    }

    public function testAddToParameterWithVariadicValues(): void
    {
        $view = TestHelper::createView();

        $view->addToParameter('test', 'a', 'b', 'c');

        $this->assertSame(['a', 'b', 'c'], $view->getParameter('test'));
    }

    public function testAddToParameterSeveral(): void
    {
        $view = TestHelper::createView();

        $view->addToParameter('test', 'a');
        $view->addToParameter('test', 'b', 'c');

        $this->assertSame(['a', 'b', 'c'], $view->getParameter('test'));
    }

    public function testAddToParameterWithNotArray(): void
    {
        $view = TestHelper::createView();

        $view->setParameter('test', 42);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "test" parameter already exists and is not an array.');
        $view->addToParameter('test', 'a', 'b');
    }

    public function testGetNotExistParameter(): void
    {
        $view = TestHelper::createView();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "id" not found.');
        $view->getParameter('id');
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

    public function testGetNotExistBlock(): void
    {
        $view = TestHelper::createView();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Block "test" not found.');
        $view->getBlock('test');
    }

    public function testPlaceholderSalt(): void
    {
        $view = TestHelper::createView()
            ->withPlaceholderSalt('apple');

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

    public function testFluentSetters(): void
    {
        $view = TestHelper::createView();

        $this->assertSame($view, $view->setBlock('test', ''));
        $this->assertSame($view, $view->removeBlock('test'));
        $this->assertSame($view, $view->setParameter('test', ''));
        $this->assertSame($view, $view->setParameters([]));
        $this->assertSame($view, $view->addToParameter('test-array'));
        $this->assertSame($view, $view->removeParameter('test'));
    }

    public function testClear(): void
    {
        $view = TestHelper::createView();
        $view->setBlock('name', 'Mike');
        $view->setParameter('age', 42);

        try {
            $view->render(__DIR__ . '/public/view/error.php');
        } catch (Exception) {
        }

        $view->clear();

        $this->assertNull($view->getViewFile());
        $this->assertFalse($view->hasBlock('name'));
        $this->assertFalse($view->hasParameter('age'));
    }

    public function testWithClearedState(): void
    {
        $view = new View();
        $view->setBlock('name', 'Mike');
        $view->setParameter('age', 42);
        $view->setTheme(new Theme());

        $newView = $view->withClearedState();

        $this->assertNull($newView->getViewFile());
        $this->assertFalse($newView->hasBlock('name'));
        $this->assertFalse($newView->hasParameter('age'));
        $this->assertNull($newView->getTheme());
    }

    public function testDeepClone(): void
    {
        $lightTheme = new Theme();
        $darkTheme = new Theme();

        $sourceView = new View();
        $sourceView->setParameter('age', 42);
        $sourceView->setLocale('ru');
        $sourceView->setTheme($lightTheme);

        $view = $sourceView->deepClone();
        $view->setParameter('age', 19);
        $view->setLocale('en');
        $view->setTheme($darkTheme);

        $this->assertNotSame($view, $sourceView);
        $this->assertSame(42, $sourceView->getParameter('age'));
        $this->assertSame('ru', $sourceView->getLocale());
        $this->assertSame($lightTheme, $sourceView->getTheme());
        $this->assertSame(19, $view->getParameter('age'));
        $this->assertSame('en', $view->getLocale());
        $this->assertSame($darkTheme, $view->getTheme());
    }

    public function testCommonStateForClonedViews(): void
    {
        $view = TestHelper::createView();
        $view->setParameter('test', 42);

        $clonedView = $view->withSourceLocale('ru');
        $clonedView->setParameter('test', 7);

        $this->assertSame(7, $view->getParameter('test'));
    }

    public function testWithBasePath(): void
    {
        $view = TestHelper::createView()->withBasePath('/hello/dir');

        $this->assertSame('/hello/dir', $view->getBasePath());
    }

    public function testImmutability(): void
    {
        $view = TestHelper::createView();

        $this->assertNotSame($view, $view->withBasePath(''));
        $this->assertNotSame($view, $view->withRenderers([new PhpTemplateRenderer()]));
        $this->assertNotSame($view, $view->withSourceLocale('en'));
        $this->assertNotSame($view, $view->withContext($this->createContext($this->tempDirectory)));
        $this->assertNotSame($view, $view->withContextPath(__DIR__));
        $this->assertNotSame($view, $view->withPlaceholderSalt(''));
        $this->assertNotSame($view, $view->withClearedState());
        $this->assertNotSame($view, $view->withLocale('es'));
        $this->assertNotSame($view, $view->withFallbackExtension('tpl'));
        $this->assertNotSame($view, $view->withTheme(null));
    }

    public function testImmutableTheme(): void
    {
        $view = TestHelper::createView();
        $theme = new Theme([]);
        $viewWithTheme = $view->withTheme($theme);

        $this->assertNull($view->getTheme());
        $this->assertNotNull($viewWithTheme->getTheme());
        $this->assertSame($theme, $viewWithTheme->getTheme());
    }

    public function testGetLocale()
    {
        $view = TestHelper::createView();

        $this->assertSame('en', $view->getLocale());

        $view->setLocale('en-US');

        $this->assertSame('en-US', $view->getLocale());
    }

    public function testWithoutBasePath(): void
    {
        $view = new View();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The base path is not set.');
        $view->getBasePath();
    }

    public function testResetBasePath(): void
    {
        $baseView = new View(__DIR__);

        $view = $baseView->withBasePath(null);

        $this->assertSame(__DIR__, $baseView->getBasePath());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The base path is not set.');
        $view->getBasePath();
    }

    private function createViewWithBasePath(string $basePath): View
    {
        return new View($basePath, new SimpleEventDispatcher());
    }

    private function createContext(string $viewPath): ViewContextInterface
    {
        return new class ($viewPath) implements ViewContextInterface {
            public function __construct(private readonly string $viewPath)
            {
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
     * @param array $items file system objects to be created in format: objectName => objectContent
     * Arrays specifies directories, other values - files.
     * @param string|null $baseDirectory base directory path.
     */
    private function createFileStructure(array $items, ?string $baseDirectory = null): void
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
