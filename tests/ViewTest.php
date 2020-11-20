<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use Yiisoft\Files\FileHelper;
use Yiisoft\View\Theme;

/**
 * ViewTest.
 */
final class ViewTest extends TestCase
{
    private string $testViewPath = '';

    public function setUp(): void
    {
        parent::setUp();

        $this->testViewPath = sys_get_temp_dir() . '/' . str_replace('\\', '_', self::class) . uniqid('', false);

        FileHelper::createDirectory($this->testViewPath);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        FileHelper::removeDirectory($this->testViewPath);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/13058
     */
    public function testExceptionOnRenderFile(): void
    {
        $view = $this->createView($this->testViewPath);

        $exceptionViewFile = $this->testViewPath . DIRECTORY_SEPARATOR . 'exception.php';
        file_put_contents(
            $exceptionViewFile,
            <<<'PHP'
<h1>Exception</h1>
<?php throw new Exception('Test Exception'); ?>
PHP
        );
        $normalViewFile = $this->testViewPath . DIRECTORY_SEPARATOR . 'no-exception.php';
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
        $themePath = $this->testViewPath . '/theme1';
        FileHelper::createDirectory($themePath);

        $baseView = "{$this->testViewPath}/theme1/base.php";
        file_put_contents(
            $baseView,
            <<<'PHP'
<?= $this->render("sub") ?>
PHP
        );

        $subView = "{$this->testViewPath}/sub.php";
        $subViewContent = 'subviewcontent';
        file_put_contents($subView, $subViewContent);

        $view = $this->createView(
            $this->testViewPath,
            new Theme([
                $this->testViewPath => $themePath,
            ])
        );

        $this->assertSame($subViewContent, $view->render('//base'));
    }

    public function testLocalizedDirectory(): void
    {
        $view = $this->createView($this->testViewPath);
        $this->createFileStructure([
            'views' => [
                'faq.php' => 'English FAQ',
                'de-DE' => [
                    'faq.php' => 'German FAQ',
                ],
            ],
        ], $this->testViewPath);
        $viewFile = $this->testViewPath . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'faq.php';
        $sourceLanguage = 'en-US';

        // Source language and target language are same. The view path should be unchanged.
        $currentLanguage = $sourceLanguage;
        $this->assertSame($viewFile, $view->localize($viewFile, $currentLanguage, $sourceLanguage));

        // Source language and target language are different. The view path should be changed.
        $currentLanguage = 'de-DE';
        $this->assertSame(
            $this->testViewPath . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $currentLanguage . DIRECTORY_SEPARATOR . 'faq.php',
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
            if (\is_array($content)) {
                if (isset($content[0], $content[1]) && $content[0] === 'symlink') {
                    symlink($baseDirectory . DIRECTORY_SEPARATOR . $content[1], $itemName);
                } else {
                    if (!mkdir($itemName, 0777, true) && !is_dir($itemName)) {
                        throw new \RuntimeException(sprintf('Directory "%s" was not created', $itemName));
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
        $this->webView->setDefaultParameters(['parameter' => 'default_parameter']);
        $output = $this->webView->render('//parameters');
        $this->assertStringContainsString('default_parameter', $output);
    }

    public function testDefaultParameterIsOverwrittenByLocalParameter(): void
    {
        $this->webView->setDefaultParameters(['parameter' => 'default_parameter']);
        $output = $this->webView->render('//parameters', [
            'parameter' => 'local_parameter',
        ]);
        $this->assertStringContainsString('local_parameter', $output);
    }

    public function testPlaceholderSalt(): void
    {
        $this->webView->setPlaceholderSalt('apple');
        $this->assertSame(dechex(crc32('apple')), $this->webView->getPlaceholderSignature());
    }
}
