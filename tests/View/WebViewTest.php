<?php
namespace Yiisoft\View\Tests;

use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Files\FileHelper;
use Yiisoft\Tests\TestCase;
use Yiisoft\View\Theme;
use Yiisoft\View\WebView;

/**
 * WebViewTest.
 */
final class WebViewTest extends TestCase
{
    /**
     * @var string $dataDir
     */
    private $dataDir;

    /**
     * @var string $layoutPath
     */
    private $layoutPath;

    /**
     * @var string path for the test files.
     */
    private $testViewPath = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = dirname(__DIR__) . '/public/view';
        $this->layoutPath = $this->dataDir . '/layout.php';
        $this->testViewPath = sys_get_temp_dir() . '/' . str_replace('\\', '_', get_class($this)) . uniqid('', false);

        FileHelper::createDirectory($this->testViewPath);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        FileHelper::removeDirectory($this->testViewPath);
    }

    public function testRegisterJsVar(): void
    {
        $view = $this->createWebView($this->dataDir);
        $view->registerJsVar('username', 'samdark');
        $html = $view->render('//layout.php', ['content' => 'content']);
        $this-> assertStringContainsString('<script>var username = "samdark";</script></head>', $html);

        $view = $this->createWebView($this->dataDir);
        $view->registerJsVar('objectTest', [
            'number' => 42,
            'question' => 'Unknown',
        ]);
        $html = $view->render('//layout.php', ['content' => 'content']);
        $this-> assertStringContainsString('<script>var objectTest = {"number":42,"question":"Unknown"};</script></head>', $html);
    }

    public function testRegisterJsFileWithAlias(): void
    {
        $view = $this->createWebView($this->testViewPath);
        $view->registerJsFile($this->aliases->get('@web/js/somefile.js'), ['position' => WebView::POS_HEAD]);
        $html = $view->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsString('<script src="/baseUrl/js/somefile.js"></script></head>', $html);

        $view = $this->createWebView($this->testViewPath);
        $view->registerJsFile($this->aliases->get('@web/js/somefile.js'), ['position' => WebView::POS_BEGIN]);
        $html = $view->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsString('<body>' . "\n" . '<script src="/baseUrl/js/somefile.js"></script>', $html);

        $view = $this->createwebView($this->testViewPath);
        $view->registerJsFile($this->aliases->get('@web/js/somefile.js'), ['position' => WebView::POS_END]);
        $html = $view->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsString('<script src="/baseUrl/js/somefile.js"></script></body>', $html);
    }

    public function testRegisterCssFileWithAlias(): void
    {
        $view = $this->createWebView($this->testViewPath);
        $view->registerCssFile($this->aliases->get('@web/css/somefile.css'));
        $html = $view->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsString('<link href="/baseUrl/css/somefile.css" rel="stylesheet"></head>', $html);
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
}
