<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Files\FileHelper;
use Yiisoft\Html\Html;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\Event\PageEnd;
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
        $this->assertStringContainsStringIgnoringLineEndings('<body>' . PHP_EOL . '<script src="/baseUrl/js/somefile.js"></script>', $html);

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
        $webView = null;
        $eventDispatcher = new SimpleEventDispatcher(static function ($event) use (&$webView) {
            if ($event instanceof PageEnd) {
                $webView->setPlaceholderSalt((string)time());
            }
        });
        $webView = $this->createWebView($eventDispatcher);
        $webView->setPlaceholderSalt('apple');
        $signature = $webView->getPlaceholderSignature();
        $html = $webView->renderFile($this->layoutPath, ['content' => 'content']);
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

    public function testRenderAjaxWithoutContext(): void
    {
        $content = 'Hello!';

        $html = $this->webView->renderAjax('//only-content.php', ['content' => $content]);

        $this->assertSame($content, $html);
    }

    public function testRegisterScriptTag(): void
    {
        $script = Html::script('{"@context": "http://schema.org/","@type": "Article","name": "Yii 3"}')
            ->type('application/ld+json');

        $this->webView->registerScriptTag($script);
        $html = $this->webView->renderFile($this->layoutPath, ['content' => '']);

        $this->assertStringContainsString($script->render(), $html);
    }

    public function testRegisterJsAndRegisterScriptTag(): void
    {
        $js1 = 'alert(1);';
        $js2 = 'alert(2);';
        $script3 = Html::script('{"@context": "http://schema.org/","@type": "Article","name": "Yii 3"}')
            ->type('application/ld+json');
        $js4 = 'alert(4);';
        $script5 = Html::script('alert("script5");');
        $script6 = Html::script('alert("script6");');
        $js7 = 'alert(7);';

        $this->webView->registerJs($js1);
        $this->webView->registerJs($js2);
        $this->webView->registerScriptTag($script3);
        $this->webView->registerJs($js4);
        $this->webView->registerScriptTag($script5);
        $this->webView->registerScriptTag($script6, WebView::POSITION_READY);
        $this->webView->registerJs($js7, WebView::POSITION_READY);
        $html = $this->webView->renderFile($this->layoutPath, ['content' => '']);

        $this->assertStringContainsString(
            "<script>$js1\n$js2</script>\n" .
            $script3->render() . "\n" .
            "<script>$js4</script>\n" .
            $script5->render(),
            $html
        );
        $this->assertStringContainsString(
            "<script>document.addEventListener('DOMContentLoaded', function(event) {\n" .
            $script6->getContent() . "\n" .
            "$js7\n" .
            '});</script>',
            $html
        );
    }

    public function testRegisterJsAndRegisterScriptTagWithAjax(): void
    {
        $js1 = 'alert(1);';
        $js2 = 'alert(2);';
        $script3 = Html::script('{"@context": "http://schema.org/","@type": "Article","name": "Yii 3"}')
            ->type('application/ld+json');
        $js4 = 'alert(4);';
        $script5 = Html::script('alert("script5");');
        $script6 = Html::script('alert("script6");');
        $js7 = 'alert(7);';

        $this->webView->registerJs($js1);
        $this->webView->registerJs($js2);
        $this->webView->registerScriptTag($script3);
        $this->webView->registerJs($js4);
        $this->webView->registerScriptTag($script5);
        $this->webView->registerScriptTag($script6, WebView::POSITION_READY);
        $this->webView->registerJs($js7, WebView::POSITION_READY);
        $html = $this->webView->renderAjax('//only-content.php', ['content' => '']);

        $this->assertStringContainsString(
            "<script>alert(1);\nalert(2);</script>\n" .
            $script3->render() . "\n" .
            "<script>alert(4);</script>\n" .
            $script5->render() . "\n" .
            $script6->render() . "\n" .
            '<script>alert(7);</script>',
            $html
        );
    }

    private function createWebView(
        ?EventDispatcherInterface $eventDispatcher = null,
        ?LoggerInterface $logger = null
    ): WebView {
        return new WebView(
            __DIR__ . '/public/view',
            $eventDispatcher ?? new SimpleEventDispatcher(),
            $logger ?? new NullLogger(),
        );
    }
}
