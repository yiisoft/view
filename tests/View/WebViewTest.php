<?php
namespace Yiisoft\View\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use yii\helpers\FileHelper;
use Yiisoft\EventDispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\View\Theme;
use Yiisoft\View\WebView;

/**
 * @group web
 */
class WebViewTest extends TestCase
{
    /**
     * @var string path for the test files.
     */
    private $testViewPath = '';

    private $layoutPath;

    private $eventDispatcher;
    private $eventProvider;

    protected function setUp()
    {
        $this->layoutPath = dirname(__DIR__) . '/data/layout.php';
        $this->testViewPath = sys_get_temp_dir() . '/' . str_replace('\\', '_', get_class($this)) . uniqid('', false);
        FileHelper::createDirectory($this->testViewPath);

        $this->eventProvider = new Provider();
        $this->eventDispatcher = new Dispatcher($this->eventProvider);
    }

    public function tearDown()
    {
        FileHelper::removeDirectory($this->testViewPath);
        $this->eventProvider = null;
        $this->eventDispatcher = null;
    }

    public function testRegisterJsVar(): void
    {
        $view = $this->createView();
        $view->registerJsVar('username', 'samdark');
        $html = $view->render($this->layoutPath, ['content' => 'content']);
        $this->assertContains('<script>var username = "samdark";</script></head>', $html);

        $view = $this->createView();
        $view->registerJsVar('objectTest', [
            'number' => 42,
            'question' => 'Unknown',
        ]);
        $html = $view->render($this->layoutPath, ['content' => 'content']);
        $this->assertContains('<script>var objectTest = {"number":42,"question":"Unknown"};</script></head>', $html);
    }

    public function testRegisterJsFileWithAlias(): void
    {
        $view = $this->createView();
        $view->registerJsFile('@web/js/somefile.js', ['position' => WebView::POS_HEAD]);
        $html = $view->render($this->layoutPath, ['content' => 'content']);
        $this->assertContains('<script src="/baseUrl/js/somefile.js"></script></head>', $html);

        $view = $this->createView();
        $view->registerJsFile('@web/js/somefile.js', ['position' => WebView::POS_BEGIN]);
        $html = $view->render($this->layoutPath, ['content' => 'content']);
        $this->assertContains('<body>' . PHP_EOL . '<script src="/baseUrl/js/somefile.js"></script>', $html);

        $view = $this->createView();
        $view->registerJsFile('@web/js/somefile.js', ['position' => WebView::POS_END]);
        $html = $view->render($this->layoutPath, ['content' => 'content']);
        $this->assertContains('<script src="/baseUrl/js/somefile.js"></script></body>', $html);
    }

    public function testRegisterCssFileWithAlias(): void
    {
        $view = $this->createView();
        $view->registerCssFile('@web/css/somefile.css');
        $html = $view->render($this->layoutPath, ['content' => 'content']);
        $this->assertContains('<link href="/baseUrl/css/somefile.css" rel="stylesheet"></head>', $html);
    }

    public function testRegisterCsrfMetaTags(): void
    {
        // TODO: can we implement CSRF with headers instead of a tag?
        // How would that work with async requests?
//        $this->mockWebApplication([], null, [
//            'request' => [
//                '__class' => Request::class,
//                'cookieValidationKey' => 'secretkey',
//                'scriptFile' => __DIR__ . '/baseUrl/index.php',
//                'scriptUrl' => '/baseUrl/index.php',
//            ],
//            'cache' => [
//                '__class' => FileCache::class,
//            ],
//        ]);

//        $view = $this->createView();
//
//        $view->registerCsrfMetaTags();
//        $html = $view->render('@yii/tests/data/views/layout.php', ['content' => 'content']);
//        $this->assertContains('<meta name="csrf-param" content="_csrf">', $html);
//        $this->assertContains('<meta name="csrf-token" content="', $html);
//        $csrfToken1 = $this->getCSRFTokenValue($html);
//
//        // regenerate token
//        $this->app->request->getCsrfToken(true);
//        $view->registerCsrfMetaTags();
//        $html = $view->render('@yii/tests/data/views/layout.php', ['content' => 'content']);
//        $this->assertContains('<meta name="csrf-param" content="_csrf">', $html);
//        $this->assertContains('<meta name="csrf-token" content="', $html);
//        $csrfToken2 = $this->getCSRFTokenValue($html);
//
//        $this->assertNotSame($csrfToken1, $csrfToken2);
    }

    /**
     * Parses CSRF token from page HTML.
     *
     * @param string $html
     * @return string CSRF token
     */
    private function getCSRFTokenValue(string $html): string
    {
        if (!preg_match('~<meta name="csrf-token" content="([^"]+)">~', $html, $matches)) {
            $this->fail("No CSRF-token meta tag found. HTML was:\n$html");
        }

        return $matches[1];
    }

    private function createView(): WebView
    {
        return new WebView($this->testViewPath, new Theme([]), $this->eventDispatcher, new NullLogger());
    }
}
