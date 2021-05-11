<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Files\FileHelper;
use Yiisoft\Html\Html;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
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
        $this->webView->registerJsFile($this->aliases->get('@baseUrl/js/somefile.js'), WebView::POSITION_HEAD);
        $html = $this->webView->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsString('<script src="/baseUrl/js/somefile.js"></script></head>', $html);

        $this->webView->registerJsFile($this->aliases->get('@baseUrl/js/somefile.js'), WebView::POSITION_BEGIN);
        $html = $this->webView->renderFile($this->layoutPath, ['content' => 'content']);
        $this->assertStringContainsStringIgnoringLineEndings('<body>' . PHP_EOL . '<script src="/baseUrl/js/somefile.js"></script>', $html);

        $this->webView->registerJsFile($this->aliases->get('@baseUrl/js/somefile.js'), WebView::POSITION_END);
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

    public function dataRegisterCss(): array
    {
        return [
            ['[HEAD]<style>.red{color:red;}</style>[/HEAD]', WebView::POSITION_HEAD],
            ['[BEGINBODY]<style>.red{color:red;}</style>[/BEGINBODY]', WebView::POSITION_BEGIN],
            ['[ENDBODY]<style>.red{color:red;}</style>[/ENDBODY]', WebView::POSITION_END],
        ];
    }

    /**
     * @dataProvider dataRegisterCss
     */
    public function testRegisterCss(string $expected, ?int $position): void
    {
        $webView = $this->createWebView();

        $position === null
            ? $webView->registerCss('.red{color:red;}')
            : $webView->registerCss('.red{color:red;}', $position);

        $html = $webView->render('//positions.php');

        $this->assertStringContainsString($expected, $html);
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

    public function testSetCssStrings(): void
    {
        $webView = $this->createWebView();

        $webView->setCssStrings([
            '.a1 { color: red; }',
            ['.a2 { color: red; }', WebView::POSITION_HEAD],
            ['.a3 { color: red; }', WebView::POSITION_BEGIN],
            ['.a4 { color: red; }', WebView::POSITION_END, 'crossorigin' => 'any'],
            'key1' => '.a5 { color: red; }',
            'key2' => ['.a6 { color: red; }'],
            'key3' => ['.a7 { color: red; }', WebView::POSITION_END],
            'key4' => ['.a8 { color: red; }', WebView::POSITION_END, 'crossorigin' => 'any'],
            Html::style('.a9 { color: red; }')->id('main'),
            [Html::style('.a10 { color: red; }')],
        ]);

        $html = $webView->render('//positions.php');

        $expected = '[BEGINPAGE][/BEGINPAGE]' . "\n" .
            '[HEAD]<style>.a1 { color: red; }' . "\n" .
            '.a2 { color: red; }' . "\n" .
            '.a5 { color: red; }' . "\n" .
            '.a6 { color: red; }</style>' . "\n" .
            '<style id="main">.a9 { color: red; }</style>' . "\n" .
            '<style>.a10 { color: red; }</style>[/HEAD]' . "\n" .
            '[BEGINBODY]<style>.a3 { color: red; }</style>[/BEGINBODY]' . "\n" .
            '[ENDBODY]<style crossorigin="any">.a4 { color: red; }</style>' . "\n" .
            '<style>.a7 { color: red; }</style>' . "\n" .
            '<style crossorigin="any">.a8 { color: red; }</style>[/ENDBODY]' . "\n" .
            '[ENDPAGE][/ENDPAGE]';

        $this->assertEqualsWithoutLE($expected, $html);
    }

    public function d1ataFailSetJsStrings(): array
    {
        return [
            ['Do not set JS string.', [[]]],
            ['Do not set JS string.', ['key' => []]],
            ['JS string should be string or instance of \Yiisoft\Html\Tag\Script. Got integer.', [[42]]],
            ['JS string should be string or instance of \Yiisoft\Html\Tag\Script. Got integer.', ['key' => [42]]],
            ['Invalid position of JS strings.', [['alert(1);', 99]]],
            ['Invalid position of JS strings.', ['key' => ['alert(1);', 99]]],
        ];
    }

    /**
     * @dataProvider dataFailSetJsStrings
     */
    public function t1estFailSetJsStrings(string $message, array $jsStrings): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $this->webView->setJsStrings($jsStrings);
    }

    public function testSetJsStrings(): void
    {
        $this->webView->setJsStrings([
            'uniqueName' => 'app1.start();',
            'app2.start();',
            'uniqueName2' => ['app3.start();', WebView::POSITION_BEGIN],
            ['app4.start();', WebView::POSITION_HEAD],
            Html::script('{"@type":"Article"}')->type('application/ld+json'),
        ]);

        $html = $this->webView->render('//rawlayout.php', ['content' => '']);

        $expected = '1<script>app4.start();</script>2<script>app3.start();</script>3<script>app1.start();' . "\n" .
            'app2.start();</script>' . "\n" .
            '<script type="application/ld+json">{"@type":"Article"}</script>4';

        $this->assertEqualsWithoutLE($expected, $html);
    }

    public function dataFailSetJsStrings(): array
    {
        return [
            ['Do not set JS string.', [[]]],
            ['Do not set JS string.', ['key' => []]],
            ['JS string should be string or instance of \Yiisoft\Html\Tag\Script. Got integer.', [[42]]],
            ['JS string should be string or instance of \Yiisoft\Html\Tag\Script. Got integer.', ['key' => [42]]],
            ['Invalid position of JS strings.', [['alert(1);', 99]]],
            ['Invalid position of JS strings.', ['key' => ['alert(1);', 99]]],
        ];
    }

    /**
     * @dataProvider dataFailSetJsStrings
     */
    public function testFailSetJsStrings(string $message, array $jsStrings): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $this->webView->setJsStrings($jsStrings);
    }

    public function testSetJsVars(): void
    {
        $this->webView->setJsVars([
            'var1' => 'value1',
            'var2' => [1, 2],
            ['var3', 'value3', WebView::POSITION_END],
        ]);

        $html = $this->webView->render('//rawlayout.php', ['content' => '']);

        $expected = '1<script>var var1 = "value1";' . "\n" .
            'var var2 = [1,2];</script>23<script>var var3 = "value3";</script>4';

        $this->assertEqualsWithoutLE($expected, $html);
    }

    public function dataFailSetJsVars(): array
    {
        return [
            ['Do not set JS variable name.', [[]]],
            ['JS variable name should be string. Got integer.', [[42]]],
            ['Do not set JS variable value.', [['var']]],
            ['Invalid position of JS variable.', [['title', 'hello', 99]]],
        ];
    }

    /**
     * @dataProvider dataFailSetJsVars
     */
    public function testFailSetJsVars(string $message, array $jsVars): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $this->webView->setJsVars($jsVars);
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
