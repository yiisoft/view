<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use Yiisoft\Files\FileHelper;
use Yiisoft\View\WebView;

final class WebViewTest extends TestCase
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
        $this->testViewPath = sys_get_temp_dir() . '/' . str_replace('\\', '_', self::class) . uniqid('', false);

        FileHelper::ensureDirectory($this->testViewPath);
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
        $this->assertStringContainsString('<script>var username = "samdark";</script></head>', $html);

        $this->webView->registerJsVar('objectTest', [
            'number' => 42,
            'question' => 'Unknown',
        ]);
        $html = $this->webView->render('//layout.php', ['content' => 'content']);
        $this->assertStringContainsString('<script>var objectTest = {"number":42,"question":"Unknown"};</script></head>', $html);
    }

    public function testRegisterJsFileWithAlias(): void
    {
        $this->webView->registerJsFile($this->aliases->get('@baseUrl/js/somefile.js'), ['position' => WebView::POSITION_HEAD]);
        $html = $this->webView->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsString('<script src="/baseUrl/js/somefile.js"></script></head>', $html);

        $this->webView->registerJsFile($this->aliases->get('@baseUrl/js/somefile.js'), ['position' => WebView::POSITION_BEGIN]);
        $html = $this->webView->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsString('<body>' . "\n" . '<script src="/baseUrl/js/somefile.js"></script>', $html);

        $this->webView->registerJsFile($this->aliases->get('@baseUrl/js/somefile.js'), ['position' => WebView::POSITION_END]);
        $html = $this->webView->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsString('<script src="/baseUrl/js/somefile.js"></script></body>', $html);
    }

    public function testRegisterCssFileWithAlias(): void
    {
        $this->webView->registerCssFile($this->aliases->get('@baseUrl/css/somefile.css'));
        $html = $this->webView->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsString('<link href="/baseUrl/css/somefile.css" rel="stylesheet"></head>', $html);
    }

    public function testPlaceholders(): void
    {
        $this->webViewPlaceholderMock->setPlaceholderSalt('apple');
        $signature = $this->webViewPlaceholderMock->getPlaceholderSignature();
        $html = $this->webViewPlaceholderMock->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsString($signature, $html);
    }

    public function testRegisterMetaTag(): void
    {
        $this->webView->registerMetaTag(['name' => 'keywords', 'content' => 'yii']);
        $html = $this->webView->renderFile($this->layoutPath, ['content' => '']);
        $this->assertStringContainsString('<meta name="keywords" content="yii"></head>', $html);
    }

    public function testRegisterLinkTag(): void
    {
        $this->webView->registerLinkTag(['href' => '/main.css']);
        $html = $this->webView->renderFile($this->layoutPath, ['content' => '']);
        $this->assertStringContainsString('<link href="/main.css"></head>', $html);
    }

    public function testRegisterCss(): void
    {
        $this->webView->registerCSs('.red{color:red;}', ['id' => 'mainCss']);
        $html = $this->webView->renderFile($this->layoutPath, ['content' => '']);
        $this->assertStringContainsString('<style id="mainCss">.red{color:red;}</style></head>', $html);
    }
}
