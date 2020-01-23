<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use Yiisoft\Files\FileHelper;
use Yiisoft\View\WebView;

final class WebViewTest extends \Yiisoft\View\Tests\TestCase
{
    private string $dataDir;
    private string $layoutPath;

    /**
     * @var string path for the test files.
     */
    private string $testViewPath = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataDir = dirname(__DIR__) . '/tests/public/view';
        $this->layoutPath = $this->dataDir . '/layout.php';
        $this->testViewPath = sys_get_temp_dir() . '/' . str_replace('\\', '_', get_class($this)) . uniqid('', false);

        FileHelper::createDirectory($this->testViewPath);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        FileHelper::removeDirectory($this->testViewPath);
    }

    public function testRegisterJsVar(): void
    {
        $this->webView->registerJsVar('username', 'samdark');
        $html = $this->webView->render('//layout.php', ['content' => 'content']);
        $this-> assertStringContainsString('<script>var username = "samdark";</script></head>', $html);

        $this->webView->registerJsVar('objectTest', [
            'number' => 42,
            'question' => 'Unknown',
        ]);
        $html = $this->webView->render('//layout.php', ['content' => 'content']);
        $this->assertStringContainsString('<script>var objectTest = {"number":42,"question":"Unknown"};</script></head>', $html);
    }

    public function testRegisterJsFileWithAlias(): void
    {
        $this->webView->registerJsFile($this->aliases->get('@web/js/somefile.js'), ['position' => WebView::POSITION_HEAD]);
        $html = $this->webView->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsString('<script src="/baseUrl/js/somefile.js"></script></head>', $html);

        $this->webView->registerJsFile($this->aliases->get('@web/js/somefile.js'), ['position' => WebView::POSITION_BEGIN]);
        $html = $this->webView->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsString('<body>' . PHP_EOL . '<script src="/baseUrl/js/somefile.js"></script>', $html);

        $this->webView->registerJsFile($this->aliases->get('@web/js/somefile.js'), ['position' => WebView::POSITION_END]);
        $html = $this->webView->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsString('<script src="/baseUrl/js/somefile.js"></script></body>', $html);
    }

    public function testRegisterCssFileWithAlias(): void
    {
        $this->webView->registerCssFile($this->aliases->get('@web/css/somefile.css'));
        $html = $this->webView->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsString('<link href="/baseUrl/css/somefile.css" rel="stylesheet"></head>', $html);
    }
}
