<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Html\Html;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\Event\WebView\BodyBegin;
use Yiisoft\View\Event\WebView\BodyEnd;
use Yiisoft\View\Event\WebView\Head;
use Yiisoft\View\Event\WebView\PageBegin;
use Yiisoft\View\Event\WebView\PageEnd;
use Yiisoft\View\Tests\TestSupport\TestHelper;
use Yiisoft\View\Tests\TestSupport\TestTrait;
use Yiisoft\View\WebView;

final class WebViewTest extends TestCase
{
    use TestTrait;

    public function dataRegisterJsVar(): array
    {
        return [
            [
                '<script>var username = "samdark";</script></head>',
                'username',
                'samdark',
            ],
            [
                '<script>var objectTest = {"number":42,"question":"Unknown"};</script></head>',
                'objectTest',
                ['number' => 42, 'question' => 'Unknown'],
            ],
        ];
    }

    /**
     * @dataProvider dataRegisterJsVar
     */
    public function testRegisterJsVar(string $expected, string $name, $value): void
    {
        $webView = TestHelper::createWebView();
        $webView->registerJsVar($name, $value);

        $html = $webView->render('//layout.php', ['content' => 'content']);
        $this->assertStringContainsString($expected, $html);
    }

    public function dataRegisterJsFile(): array
    {
        return [
            ['http://example.com/main.js'],
            ['https://example.com/main.js'],
            ['//example.com/main.js'],
            ['main.js'],
            ['../../main.js'],
            ['/main.js'],
        ];
    }

    /**
     * @dataProvider dataRegisterJsFile
     */
    public function testRegisterJsFile(string $url): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerJsFile($url);

        $html = $webView->render('//positions.php');
        $this->assertStringContainsString('[ENDBODY]<script src="' . $url . '"></script>[/ENDBODY]', $html);
    }

    public function dataRegisterJsFileWithPosition(): array
    {
        return [
            [
                '[HEAD]<script src="/somefile.js"></script>[/HEAD]',
                WebView::POSITION_HEAD,
            ],
            [
                '[BEGINBODY]<script src="/somefile.js"></script>[/BEGINBODY]',
                WebView::POSITION_BEGIN,
            ],
            [
                '[ENDBODY]<script src="/somefile.js"></script>[/ENDBODY]',
                WebView::POSITION_END,
            ],
        ];
    }

    /**
     * @dataProvider dataRegisterJsFileWithPosition
     */
    public function testRegisterJsFileWithPosition(string $expected, int $position): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerJsFile('/somefile.js', $position);

        $html = $webView->render('//positions.php');
        $this->assertStringContainsString($expected, $html);
    }

    public function dataRegisterCssFile(): array
    {
        return [
            ['http://example.com/main.css'],
            ['https://example.com/main.css'],
            ['//example.com/main.css'],
            ['main.css'],
            ['../../main.css'],
            ['/main.css'],
        ];
    }

    /**
     * @dataProvider dataRegisterCssFile
     */
    public function testRegisterCssFile(string $url): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerCssFile($url);

        $html = $webView->render('//positions.php');
        $this->assertStringContainsString('[HEAD]<link href="' . $url . '" rel="stylesheet">[/HEAD]', $html);
    }

    public function dataRegisterCssFileWithPosition(): array
    {
        return [
            [
                '[HEAD]<link href="/somefile.css" rel="stylesheet">[/HEAD]',
                WebView::POSITION_HEAD,
            ],
            [
                '[BEGINBODY]<link href="/somefile.css" rel="stylesheet">[/BEGINBODY]',
                WebView::POSITION_BEGIN,
            ],
            [
                '[ENDBODY]<link href="/somefile.css" rel="stylesheet">[/ENDBODY]',
                WebView::POSITION_END,
            ],
        ];
    }

    /**
     * @dataProvider dataRegisterCssFileWithPosition
     */
    public function testRegisterCssFileWithPosition(string $expected, int $position): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerCssFile('/somefile.css', $position);

        $html = $webView->render('//positions.php');
        $this->assertStringContainsString($expected, $html);
    }

    public function testPlaceholders(): void
    {
        $webView = null;
        $eventDispatcher = new SimpleEventDispatcher(static function ($event) use (&$webView) {
            if ($event instanceof PageEnd) {
                $webView->setPlaceholderSalt((string)time());
            }
        });
        $webView = TestHelper::createWebView($eventDispatcher);
        $webView->setPlaceholderSalt('apple');
        $signature = $webView->getPlaceholderSignature();
        $html = $webView->render('//layout.php', ['content' => 'content']);
        $this->assertStringContainsString($signature, $html);
    }

    public function testRegisterMeta(): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerMeta([
            'name' => 'keywords',
            'content' => 'yii',
        ]);

        $html = $webView->render('//positions.php');

        $this->assertStringContainsString('[HEAD]<meta name="keywords" content="yii">[/HEAD]', $html);
    }

    public function testRegisterMetaTag(): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerMetaTag(Html::meta([
            'name' => 'keywords',
            'content' => 'yii',
        ]));

        $html = $webView->render('//positions.php');

        $this->assertStringContainsString('[HEAD]<meta name="keywords" content="yii">[/HEAD]', $html);
    }

    public function dataRegisterLink(): array
    {
        return [
            ['[HEAD]<link href="/main.css">[/HEAD]', WebView::POSITION_HEAD],
            ['[BEGINBODY]<link href="/main.css">[/BEGINBODY]', WebView::POSITION_BEGIN],
            ['[ENDBODY]<link href="/main.css">[/ENDBODY]', WebView::POSITION_END],
        ];
    }

    /**
     * @dataProvider dataRegisterLink
     */
    public function testRegisterLink(string $expected, ?int $position): void
    {
        $webView = TestHelper::createWebView();

        $position === null
            ? $webView->registerLink(['href' => '/main.css'])
            : $webView->registerLink(['href' => '/main.css'], $position);

        $html = $webView->render('//positions.php');

        $this->assertStringContainsString($expected, $html);
    }

    /**
     * @dataProvider dataRegisterLink
     */
    public function testRegisterLinkTag(string $expected, ?int $position): void
    {
        $webView = TestHelper::createWebView();

        $link = Html::link()->href('/main.css');

        $position === null
            ? $webView->registerLinkTag($link)
            : $webView->registerLinkTag($link, $position);

        $html = $webView->render('//positions.php');

        $this->assertStringContainsString($expected, $html);
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
        $webView = TestHelper::createWebView();

        $position === null
            ? $webView->registerCss('.red{color:red;}')
            : $webView->registerCss('.red{color:red;}', $position);

        $html = $webView->render('//positions.php');

        $this->assertStringContainsString($expected, $html);
    }

    public function testRegisterCssWithAttributes(): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerCss(
            '.red{color:red;}',
            WebView::POSITION_HEAD,
            ['id' => 'main'],
        );

        $html = $webView->render('//positions.php');

        $this->assertStringContainsString('[HEAD]<style id="main">.red{color:red;}</style>[/HEAD]', $html);
    }

    public function testRegisterCssFromFile(): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerCssFromFile(__DIR__ . '/public/example.css', WebView::POSITION_HEAD, ['id' => 'main']);

        $html = $webView->render('//positions.php');

        $this->assertStringContainsString(
            '[HEAD]<style id="main">A { color: blue; }' . "\n" . '</style>[/HEAD]',
            $html
        );
    }

    public function testRenderAjaxWithoutContext(): void
    {
        $content = 'Hello!';

        $html = TestHelper::createWebView()->renderAjax('//only-content.php', ['content' => $content]);

        $this->assertSame($content, $html);
    }

    public function testRenderAjaxString(): void
    {
        $eventDispatcher = new SimpleEventDispatcher();
        $webView = TestHelper::createWebView($eventDispatcher);

        $string = 'content';
        $result = $webView->renderAjaxString($string);

        $this->assertSame($string, $result);
        $this->assertSame(
            [
                PageBegin::class,
                Head::class,
                BodyBegin::class,
                BodyEnd::class,
                PageEnd::class,
            ],
            $eventDispatcher->getEventClasses()
        );
    }

    public function testRegisterScriptTag(): void
    {
        $webView = TestHelper::createWebView();

        $script = Html::script('{"@context": "http://schema.org/","@type": "Article","name": "Yii 3"}')
            ->type('application/ld+json');

        $webView->registerScriptTag($script);
        $html = $webView->render('//positions.php');

        $expected = '[BEGINPAGE][/BEGINPAGE]' . "\n" .
            '[HEAD][/HEAD]' . "\n" .
            '[BEGINBODY][/BEGINBODY]' . "\n" .
            '[ENDBODY]' . $script->render() . '[/ENDBODY]' . "\n" .
            '[ENDPAGE][/ENDPAGE]';

        $this->assertSame($expected, $html);
    }

    public function testRegisterJsAndRegisterScriptTag(): void
    {
        $webView = TestHelper::createWebView();

        $js1 = 'alert(1);';
        $js2 = 'alert(2);';
        $script3 = Html::script('{"@context": "http://schema.org/","@type": "Article","name": "Yii 3"}')
            ->type('application/ld+json');
        $js4 = 'alert(4);';
        $script5 = Html::script('alert("script5");');
        $script6 = Html::script('alert("script6");');
        $js7 = 'alert(7);';

        $webView->registerJs($js1);
        $webView->registerJs($js2);
        $webView->registerScriptTag($script3);
        $webView->registerJs($js4);
        $webView->registerScriptTag($script5);
        $webView->registerScriptTag($script6, WebView::POSITION_READY);
        $webView->registerJs($js7, WebView::POSITION_READY);
        $html = $webView->render('//layout.php', ['content' => '']);

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
        $webView = TestHelper::createWebView();

        $js1 = 'alert(1);';
        $js2 = 'alert(2);';
        $script3 = Html::script('{"@context": "http://schema.org/","@type": "Article","name": "Yii 3"}')
            ->type('application/ld+json');
        $js4 = 'alert(4);';
        $script5 = Html::script('alert("script5");');
        $script6 = Html::script('alert("script6");');
        $js7 = 'alert(7);';

        $webView->registerJs($js1);
        $webView->registerJs($js2);
        $webView->registerScriptTag($script3);
        $webView->registerJs($js4);
        $webView->registerScriptTag($script5);
        $webView->registerScriptTag($script6, WebView::POSITION_READY);
        $webView->registerJs($js7, WebView::POSITION_READY);
        $html = $webView->renderAjax('//only-content.php', ['content' => '']);

        $expected = "<script>$js1\n$js2</script>\n" .
            $script3->render() . "\n" .
            "<script>$js4</script>\n" .
            $script5->render() . "\n" .
            $script6->render() . "\n" .
            "<script>$js7</script>";

        $this->assertSame($expected, $html);
    }

    public function testAddCssStrings(): void
    {
        $webView = TestHelper::createWebView();

        $webView->addCssStrings([
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
            [Html::style('.a11 { color: red; }'), 'id' => 'second'],
        ]);

        $html = $webView->render('//positions.php');

        $expected = '[BEGINPAGE][/BEGINPAGE]' . "\n" .
            '[HEAD]<style>.a1 { color: red; }' . "\n" .
            '.a2 { color: red; }' . "\n" .
            '.a5 { color: red; }' . "\n" .
            '.a6 { color: red; }</style>' . "\n" .
            '<style id="main">.a9 { color: red; }</style>' . "\n" .
            '<style>.a10 { color: red; }</style>' . "\n" .
            '<style id="second">.a11 { color: red; }</style>[/HEAD]' . "\n" .
            '[BEGINBODY]<style>.a3 { color: red; }</style>[/BEGINBODY]' . "\n" .
            '[ENDBODY]<style crossorigin="any">.a4 { color: red; }</style>' . "\n" .
            '<style>.a7 { color: red; }</style>' . "\n" .
            '<style crossorigin="any">.a8 { color: red; }</style>[/ENDBODY]' . "\n" .
            '[ENDPAGE][/ENDPAGE]';

        $this->assertEqualStringsIgnoringLineEndings($expected, $html);
    }

    public function testAddJsStrings(): void
    {
        $webView = TestHelper::createWebView();

        $webView->addJsStrings([
            'uniqueName' => 'app1.start();',
            'app2.start();',
            'uniqueName2' => ['app3.start();', WebView::POSITION_BEGIN],
            ['app4.start();', WebView::POSITION_HEAD],
            Html::script('{"@type":"Article"}')->type('application/ld+json'),
        ]);

        $html = $webView->render('//positions.php');

        $expected = '[BEGINPAGE][/BEGINPAGE]' . "\n" .
            '[HEAD]<script>app4.start();</script>[/HEAD]' . "\n" .
            '[BEGINBODY]<script>app3.start();</script>[/BEGINBODY]' . "\n" .
            "[ENDBODY]<script>app1.start();\napp2.start();</script>\n" .
            '<script type="application/ld+json">{"@type":"Article"}</script>[/ENDBODY]' . "\n" .
            '[ENDPAGE][/ENDPAGE]';

        $this->assertSame($expected, $html);
    }

    public function dataFailAddJsStrings(): array
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
     * @dataProvider dataFailAddJsStrings
     */
    public function testFailAddJsStrings(string $message, array $jsStrings): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        TestHelper::createWebView()->addJsStrings($jsStrings);
    }

    public function testAddJsVars(): void
    {
        $webView = TestHelper::createWebView();

        $webView->addJsVars([
            'var1' => 'value1',
            'var2' => [1, 2],
            ['var3', 'value3', WebView::POSITION_END],
        ]);

        $html = $webView->render('//positions.php');

        $expected = '[BEGINPAGE][/BEGINPAGE]' . "\n" .
            '[HEAD]<script>var var1 = "value1";' . "\n" .
            'var var2 = [1,2];</script>[/HEAD]' . "\n" .
            '[BEGINBODY][/BEGINBODY]' . "\n" .
            '[ENDBODY]<script>var var3 = "value3";</script>[/ENDBODY]' . "\n" .
            '[ENDPAGE][/ENDPAGE]';

        $this->assertSame($expected, $html);
    }

    public function dataFailAddJsVars(): array
    {
        return [
            ['Do not set JS variable name.', [[]]],
            ['JS variable name should be string. Got integer.', [[42]]],
            ['Do not set JS variable value.', [['var']]],
            ['Invalid position of JS variable.', [['title', 'hello', 99]]],
        ];
    }

    /**
     * @dataProvider dataFailAddJsVars
     */
    public function testFailAddJsVars(string $message, array $jsVars): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        TestHelper::createWebView()->addJsVars($jsVars);
    }
}
