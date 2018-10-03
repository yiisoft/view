<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\view\tests\unit\view\ViewTest;

use yii\view\Theme;
use yii\view\View;
use yii\view\ViewEvent;
use yii\helpers\FileHelper;
use yii\tests\TestCase;

/**
 * @group view
 */
class ViewTest extends TestCase
{
    /**
     * @var string path for the test files.
     */
    protected $testViewPath = '';

    public function setUp()
    {
        parent::setUp();

        $this->mockApplication();
        $this->testViewPath = $this->app->getAlias('@yii/tests/runtime') . DIRECTORY_SEPARATOR . str_replace('\\', '_', get_class($this)) . uniqid();
        FileHelper::createDirectory($this->testViewPath);
    }

    public function tearDown()
    {
        FileHelper::removeDirectory($this->testViewPath);
        parent::tearDown();
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/13058
     */
    public function testExceptionOnRenderFile()
    {
        $view = new View($this->app);

        $exceptionViewFile = $this->testViewPath . DIRECTORY_SEPARATOR . 'exception.php';
        file_put_contents($exceptionViewFile, <<<'PHP'
<h1>Exception</h1>
<?php throw new Exception('Test Exception'); ?>
PHP
);
        $normalViewFile = $this->testViewPath . DIRECTORY_SEPARATOR . 'no-exception.php';
        file_put_contents($normalViewFile, <<<'PHP'
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

    public function testRelativePathInView()
    {
        $view = new View($this->app);
        FileHelper::createDirectory($this->testViewPath . '/theme1');
        $this->app->setAlias('@testviews', $this->testViewPath);
        $this->app->setAlias('@theme', $this->testViewPath . '/theme1');

        $baseView = "{$this->testViewPath}/theme1/base.php";
        file_put_contents($baseView, <<<'PHP'
<?php 
    echo $this->render("sub"); 
?>
PHP
        );

        $subView = "{$this->testViewPath}/sub.php";
        $subViewContent = "subviewcontent";
        file_put_contents($subView, $subViewContent);

        $view->theme = new Theme([
            'pathMap' => [
                '@testviews' => '@theme'
            ]
        ]);

        $this->assertSame($subViewContent, $view->render('@testviews/base'));
    }

    /// FIXME
    /// copied from FileHelperTest without required fixes
    public function testLocalizedDirectory()
    {
        $this->createFileStructure([
            'views' => [
                'faq.php' => 'English FAQ',
                'de-DE' => [
                    'faq.php' => 'German FAQ',
                ],
            ],
        ]);
        $viewFile = $this->testFilePath . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'faq.php';
        $sourceLanguage = 'en-US';

        // Source language and target language are same. The view path should be unchanged.
        $currentLanguage = $sourceLanguage;
        $this->assertSame($viewFile, $this->view->localize($viewFile, $currentLanguage, $sourceLanguage));

        // Source language and target language are different. The view path should be changed.
        $currentLanguage = 'de-DE';
        $this->assertSame(
            $this->testFilePath . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $currentLanguage . DIRECTORY_SEPARATOR . 'faq.php',
            $this->view->localize($viewFile, $currentLanguage, $sourceLanguage)
        );
    }
}
