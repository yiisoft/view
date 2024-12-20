<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Link;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Html\Tag\Script;
use Yiisoft\Html\Tag\Style;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\Event\WebView\AfterRender;
use Yiisoft\View\Event\WebView\BeforeRender;
use Yiisoft\View\Event\WebView\BodyBegin;
use Yiisoft\View\Event\WebView\BodyEnd;
use Yiisoft\View\Event\WebView\Head;
use Yiisoft\View\Event\WebView\PageBegin;
use Yiisoft\View\Event\WebView\PageEnd;
use Yiisoft\View\Tests\TestSupport\TestHelper;
use Yiisoft\View\Tests\TestSupport\TestTrait;
use Yiisoft\View\Theme;
use Yiisoft\View\WebView;

final class WebViewTest extends TestCase
{
    use TestTrait;

    public static function dataRegisterJsVar(): array
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

    #[DataProvider('dataRegisterJsVar')]
    public function testRegisterJsVar(string $expected, string $name, $value): void
    {
        $webView = TestHelper::createWebView();
        $webView->registerJsVar($name, $value);

        $html = $webView->render('layout.php', ['content' => 'content']);
        $this->assertStringContainsString($expected, $html);
    }

    public static function dataRegisterJsFile(): array
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

    #[DataProvider('dataRegisterJsFile')]
    public function testRegisterJsFile(string $url): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerJsFile($url);

        $html = $webView->render('positions.php');
        $this->assertStringContainsString('[ENDBODY]<script src="' . $url . '"></script>[/ENDBODY]', $html);
    }

    public function testRegisterJsFileWithInvalidPosition(): void
    {
        $webView = TestHelper::createWebView();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid position of JS file.');
        $webView->registerJsFile('/somefile.js', 42);
    }

    public static function dataRegisterJsFileWithPosition(): array
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

    #[DataProvider('dataRegisterJsFileWithPosition')]
    public function testRegisterJsFileWithPosition(string $expected, int $position): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerJsFile('/somefile.js', $position);

        $html = $webView->render('positions.php');
        $this->assertStringContainsString($expected, $html);
    }

    public function testRegisterJsFileWithKeyEdgeCase(): void
    {
        $webView = TestHelper::createWebView()
            ->registerJsFile('main.js')
            ->registerJsFile('main.js', key: '')
            ->registerJsFile('main.js', key: '0')
            ->registerJsFile('main.js', key: 'four');

        $html = $webView->render('positions.php');

        $this->assertStringContainsString(
            <<<HTML
            [ENDBODY]<script src="main.js"></script>
            <script src="main.js"></script>
            <script src="main.js"></script>
            <script src="main.js"></script>[/ENDBODY]
            HTML,
            $html
        );
    }

    public function testRegisterStyleTag(): void
    {
        $webView = TestHelper::createWebView();

        $style = Html::style('H1 { color: red; }', ['id' => 'H1Css']);

        $webView->registerStyleTag($style);
        $html = $webView->render('positions.php');

        $expected = '[BEGINPAGE][/BEGINPAGE]' . "\n" .
            '[HEAD]' . $style->render() . '[/HEAD]' . "\n" .
            '[BEGINBODY][/BEGINBODY]' . "\n" .
            '[ENDBODY][/ENDBODY]' . "\n" .
            '[ENDPAGE][/ENDPAGE]';

        $this->assertSame($expected, $html);
    }

    public function testRegisterStyleTagEdgeCase(): void
    {
        $style = Html::style('H1 { color: red; }');
        $webView = TestHelper::createWebView()
            ->registerStyleTag($style)
            ->registerStyleTag($style, key: '')
            ->registerStyleTag($style, key: '0')
            ->registerStyleTag($style, key: 'test');

        $html = $webView->render('positions.php');

        $expected = <<<CODE
            [BEGINPAGE][/BEGINPAGE]
            [HEAD]{$style->render()}
            {$style->render()}
            {$style->render()}
            {$style->render()}[/HEAD]
            [BEGINBODY][/BEGINBODY]
            [ENDBODY][/ENDBODY]
            [ENDPAGE][/ENDPAGE]
            CODE;

        $this->assertSame($expected, $html);
    }

    public static function dataRegisterCssFile(): array
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

    #[DataProvider('dataRegisterCssFile')]
    public function testRegisterCssFile(string $url): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerCssFile($url);

        $html = $webView->render('positions.php');
        $this->assertStringContainsString('[HEAD]<link href="' . $url . '" rel="stylesheet">[/HEAD]', $html);
    }

    public function testRegisterCssFileEdgeCase(): void
    {
        $webView = TestHelper::createWebView()
            ->registerCssFile('main.css')
            ->registerCssFile('main.css', options: ['data-x' => '1'], key: '')
            ->registerCssFile('main.css', options: ['data-x' => '2'], key: '0');

        $html = $webView->render('positions.php');

        $this->assertStringContainsString(
            <<<HTML
            [HEAD]<link href="main.css" rel="stylesheet">
            <link href="main.css" rel="stylesheet" data-x="1">
            <link href="main.css" rel="stylesheet" data-x="2">[/HEAD]
            HTML,
            $html
        );
    }

    public function testRegisterCssFileWithInvalidPosition(): void
    {
        $webView = TestHelper::createWebView();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid position of CSS file.');
        $webView->registerCssFile('/somefile.css', 42);
    }

    public static function dataRegisterCssFileWithPosition(): array
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

    #[DataProvider('dataRegisterCssFileWithPosition')]
    public function testRegisterCssFileWithPosition(string $expected, int $position): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerCssFile('/somefile.css', $position);

        $html = $webView->render('positions.php');
        $this->assertStringContainsString($expected, $html);
    }

    public function testRegisterMeta(): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerMeta([
            'name' => 'keywords',
            'content' => 'yii',
        ]);

        $html = $webView->render('positions.php');

        $this->assertStringContainsString('[HEAD]<meta name="keywords" content="yii">[/HEAD]', $html);
    }

    public function testRegisterMetaTagAppend(): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerMetaTag(Html::meta([
            'name' => 'keywords',
            'content' => 'yii',
        ]));

        $webView->registerMetaTag(Html::meta([
            'name' => 'keywords',
            'content' => 'yii3',
        ]));

        $html = $webView->render('positions.php');

        $this->assertStringContainsString('<meta name="keywords" content="yii">', $html);
        $this->assertStringContainsString('<meta name="keywords" content="yii3">', $html);
    }

    public function testRegisterMetaTagOverride(): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerMetaTag(Html::meta([
            'name' => 'keywords',
            'content' => 'yii',
        ]), 'keywords');

        $webView->registerMetaTag(Html::meta([
            'name' => 'keywords',
            'content' => 'yii3',
        ]), 'keywords');

        $html = $webView->render('positions.php');

        $this->assertStringContainsString('<meta name="keywords" content="yii3">', $html);
        $this->assertStringNotContainsString('<meta name="keywords" content="yii">', $html);
    }

    public static function dataRegisterLink(): array
    {
        return [
            ['[HEAD]<link href="/main.css">[/HEAD]', WebView::POSITION_HEAD],
            ['[BEGINBODY]<link href="/main.css">[/BEGINBODY]', WebView::POSITION_BEGIN],
            ['[ENDBODY]<link href="/main.css">[/ENDBODY]', WebView::POSITION_END],
        ];
    }

    #[DataProvider('dataRegisterLink')]
    public function testRegisterLink(string $expected, ?int $position): void
    {
        $webView = TestHelper::createWebView();

        $position === null
            ? $webView->registerLink(['href' => '/main.css'])
            : $webView->registerLink(['href' => '/main.css'], $position);

        $html = $webView->render('positions.php');

        $this->assertStringContainsString($expected, $html);
    }

    #[DataProvider('dataRegisterLink')]
    public function testRegisterLinkTag(string $expected, ?int $position): void
    {
        $webView = TestHelper::createWebView();

        $link = Html::link()->href('/main.css');

        $position === null
            ? $webView->registerLinkTag($link)
            : $webView->registerLinkTag($link, $position);

        $html = $webView->render('positions.php');

        $this->assertStringContainsString($expected, $html);
    }

    public function testRegisterLinkTagOverride(): void
    {
        $webView = TestHelper::createWebView();

        $link1 = Html::link()->href('/main1.css');
        $link2 = Html::link()->href('/main2.css');

        $webView->registerLinkTag($link1, WebView::POSITION_HEAD, 'unique');
        $webView->registerLinkTag($link2, WebView::POSITION_HEAD, 'unique');

        $html = $webView->render('positions.php');

        $this->assertStringContainsString('<link href="/main2.css">', $html);
        $this->assertStringNotContainsString('<link href="/main1.css">', $html);
    }

    public static function dataRegisterCss(): array
    {
        return [
            ['[HEAD]<style>.red{color:red;}</style>[/HEAD]', WebView::POSITION_HEAD],
            ['[BEGINBODY]<style>.red{color:red;}</style>[/BEGINBODY]', WebView::POSITION_BEGIN],
            ['[ENDBODY]<style>.red{color:red;}</style>[/ENDBODY]', WebView::POSITION_END],
        ];
    }

    #[DataProvider('dataRegisterCss')]
    public function testRegisterCss(string $expected, ?int $position): void
    {
        $webView = TestHelper::createWebView();

        $position === null
            ? $webView->registerCss('.red{color:red;}')
            : $webView->registerCss('.red{color:red;}', $position);

        $html = $webView->render('positions.php');

        $this->assertStringContainsString($expected, $html);
    }

    public function testRegisterCssWithKeyEdgeCase(): void
    {
        $webView = TestHelper::createWebView()
            ->registerCss('.red{color:red;}')
            ->registerCss('.red{color:red;}', key: '')
            ->registerCss('.red{color:red;}', key: '0')
            ->registerCss('.red{color:red;}', key: 'red');

        $html = $webView->render('positions.php');

        $this->assertStringContainsString(
            <<<CSS
            .red{color:red;}
            .red{color:red;}
            .red{color:red;}
            CSS,
            $html
        );
    }

    public function testRegisterCssWithAttributes(): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerCss(
            '.red{color:red;}',
            WebView::POSITION_HEAD,
            ['id' => 'main'],
        );

        $html = $webView->render('positions.php');

        $this->assertStringContainsString('[HEAD]<style id="main">.red{color:red;}</style>[/HEAD]', $html);
    }

    public function testRegisterCssFromFile(): void
    {
        $webView = TestHelper::createWebView();

        $webView->registerCssFromFile(__DIR__ . '/public/example.css', WebView::POSITION_HEAD, ['id' => 'main']);

        $html = $webView->render('positions.php');

        $this->assertStringContainsString(
            '[HEAD]<style id="main">A { color: blue; }</style>[/HEAD]',
            $html
        );
    }

    public function testRegisterCssFromNonExistsFile(): void
    {
        $webView = TestHelper::createWebView();

        $exception = null;

        set_error_handler(static fn () => null);
        try {
            $webView->registerCssFromFile('/non-exists-file.css');
        } catch (Throwable $e) {
            $exception = $e;
        } finally {
            restore_error_handler();
        }

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertSameIgnoringSlash('File /non-exists-file.css could not be read.', $exception->getMessage());
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

    public function testRenderEvents(): void
    {
        $eventDispatcher = new SimpleEventDispatcher();
        $webView = TestHelper::createWebView($eventDispatcher);

        $webView->render('layout.php', ['content' => '']);

        $this->assertSame(
            [
                BeforeRender::class,
                PageBegin::class,
                Head::class,
                BodyBegin::class,
                BodyEnd::class,
                PageEnd::class,
                AfterRender::class,
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
        $html = $webView->render('positions.php');

        $expected = '[BEGINPAGE][/BEGINPAGE]' . "\n" .
            '[HEAD][/HEAD]' . "\n" .
            '[BEGINBODY][/BEGINBODY]' . "\n" .
            '[ENDBODY]' . $script->render() . '[/ENDBODY]' . "\n" .
            '[ENDPAGE][/ENDPAGE]';

        $this->assertSame($expected, $html);
    }

    public function testRegisterScriptTagWithKeyEdgeCase(): void
    {
        $script = Html::script('alert(1);');
        $webView = TestHelper::createWebView()
            ->registerScriptTag($script)
            ->registerScriptTag($script, key: '')
            ->registerScriptTag($script, key: '0')
            ->registerScriptTag($script, key: 'four');

        $html = $webView->render('positions.php');

        $expected = <<<HTML
            [BEGINPAGE][/BEGINPAGE]
            [HEAD][/HEAD]
            [BEGINBODY][/BEGINBODY]
            [ENDBODY]{$script->render()}
            {$script->render()}
            {$script->render()}
            {$script->render()}[/ENDBODY]
            [ENDPAGE][/ENDPAGE]
            HTML;

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
        $js8 = 'alert(8);';

        $webView->registerJs($js1);
        $webView->registerJs($js2);
        $webView->registerScriptTag($script3);
        $webView->registerJs($js4);
        $webView->registerScriptTag($script5);
        $webView->registerScriptTag($script6, WebView::POSITION_READY);
        $webView->registerJs($js7, WebView::POSITION_READY);
        $webView->registerJs($js8, WebView::POSITION_LOAD);
        $html = $webView->render('layout.php', ['content' => '']);

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
        $this->assertStringContainsString(
            "<script>window.addEventListener('load', function(event) {\n" .
            "$js8\n" .
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

    public function testRegisterJsWithKeyEdgeCase(): void
    {
        $webView = TestHelper::createWebView()
            ->registerJs('alert(1);')
            ->registerJs('alert(1);', key: '')
            ->registerJs('alert(1);', key: '0')
            ->registerJs('alert(1);', key: 'four');

        $html = $webView->render('positions.php');

        $this->assertStringContainsString(
            <<<JS
            <script>alert(1);
            alert(1);
            alert(1);
            alert(1);</script>
            JS,
            $html
        );
    }

    public function testAddCssFiles(): void
    {
        $webView = TestHelper::createWebView();

        $webView->addCssFiles([
            'file-1.css',
            ['file-2.css', 'crossorigin' => 'anonymous', WebView::POSITION_BEGIN],
        ]);

        $html = $webView->render('positions.php');

        $expected = '[BEGINPAGE][/BEGINPAGE]' . "\n" .
            '[HEAD]<link href="file-1.css" rel="stylesheet">[/HEAD]' . "\n" .
            '[BEGINBODY]<link href="file-2.css" rel="stylesheet" crossorigin="anonymous">[/BEGINBODY]' . "\n" .
            '[ENDBODY][/ENDBODY]' . "\n" .
            '[ENDPAGE][/ENDPAGE]';

        $this->assertSame($expected, $html);
    }

    public static function dataFailAddCssFiles(): array
    {
        return [
            ['Do not set CSS file.', [[]]],
            ['CSS file should be string. Got int.', [[42]]],
            ['Invalid position of CSS file.', [['file.css', 99]]],
        ];
    }

    #[DataProvider('dataFailAddCssFiles')]
    public function testFailAddCssFiles(string $message, array $cssFiles): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        TestHelper::createWebView()->addCssFiles($cssFiles);
    }

    public function testAddJsFiles(): void
    {
        $webView = TestHelper::createWebView();

        $webView->addJsFiles([
            'file-1.js',
            ['file-2.js', 'async' => true, WebView::POSITION_BEGIN],
        ]);

        $html = $webView->render('positions.php');

        $expected = '[BEGINPAGE][/BEGINPAGE]' . "\n" .
            '[HEAD][/HEAD]' . "\n" .
            '[BEGINBODY]<script src="file-2.js" async></script>[/BEGINBODY]' . "\n" .
            '[ENDBODY]<script src="file-1.js"></script>[/ENDBODY]' . "\n" .
            '[ENDPAGE][/ENDPAGE]';

        $this->assertSame($expected, $html);
    }

    public static function dataFailAddJsFiles(): array
    {
        return [
            ['Do not set JS file.', [[]]],
            ['JS file should be string. Got int.', [[42]]],
            ['Invalid position of JS file.', [['file.js', 99]]],
        ];
    }

    #[DataProvider('dataFailAddJsFiles')]
    public function testFailAddJsFiles(string $message, array $jsFiles): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        TestHelper::createWebView()->addJsFiles($jsFiles);
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

        $html = $webView->render('positions.php');

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

    public static function dataFailAddCssStrings(): array
    {
        return [
            ['Do not set CSS string.', [[]]],
            ['Do not set CSS string.', ['key' => []]],
            ['CSS string should be string or instance of \Yiisoft\Html\Tag\Style. Got int.', [[42]]],
            ['CSS string should be string or instance of \Yiisoft\Html\Tag\Style. Got int.', ['key' => [42]]],
            ['Invalid position of CSS strings.', [['.a1 { color: red; }', 99]]],
            ['Invalid position of CSS strings.', ['key' => ['.a1 { color: red; }', 99]]],
        ];
    }

    #[DataProvider('dataFailAddCssStrings')]
    public function testFailAddCssStrings(string $message, array $cssStrings): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        TestHelper::createWebView()->addCssStrings($cssStrings);
    }

    public function testAddJsStrings(): void
    {
        $webView = TestHelper::createWebView();

        $webView->addJsStrings([
            'uniqueName' => 'app1.start();',
            'app2.start();',
            'uniqueName2' => ['app3.start();', WebView::POSITION_BEGIN],
            ['app4.start();', WebView::POSITION_HEAD, 'async' => true],
            Html::script('{"@type":"Article"}')->type('application/ld+json'),
        ]);

        $html = $webView->render('positions.php');

        $expected = '[BEGINPAGE][/BEGINPAGE]' . "\n" .
            '[HEAD]<script async>app4.start();</script>[/HEAD]' . "\n" .
            '[BEGINBODY]<script>app3.start();</script>[/BEGINBODY]' . "\n" .
            "[ENDBODY]<script>app1.start();\napp2.start();</script>\n" .
            '<script type="application/ld+json">{"@type":"Article"}</script>[/ENDBODY]' . "\n" .
            '[ENDPAGE][/ENDPAGE]';

        $this->assertSame($expected, $html);
    }

    public static function dataFailAddJsStrings(): array
    {
        return [
            ['Do not set JS string.', [[]]],
            ['Do not set JS string.', ['key' => []]],
            ['JS string should be string or instance of \Yiisoft\Html\Tag\Script. Got int.', [[42]]],
            ['JS string should be string or instance of \Yiisoft\Html\Tag\Script. Got int.', ['key' => [42]]],
            ['Invalid position of JS strings.', [['alert(1);', 99]]],
            ['Invalid position of JS strings.', ['key' => ['alert(1);', 99]]],
        ];
    }

    #[DataProvider('dataFailAddJsStrings')]
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

        $html = $webView->render('positions.php');

        $expected = '[BEGINPAGE][/BEGINPAGE]' . "\n" .
            '[HEAD]<script>var var1 = "value1";' . "\n" .
            'var var2 = [1,2];</script>[/HEAD]' . "\n" .
            '[BEGINBODY][/BEGINBODY]' . "\n" .
            '[ENDBODY]<script>var var3 = "value3";</script>[/ENDBODY]' . "\n" .
            '[ENDPAGE][/ENDPAGE]';

        $this->assertSame($expected, $html);
    }

    public static function dataFailAddJsVars(): array
    {
        return [
            ['Do not set JS variable name.', [[]]],
            ['JS variable name should be string. Got int.', [[42]]],
            ['Do not set JS variable value.', [['var']]],
            ['Invalid position of JS variable.', [['title', 'hello', 99]]],
        ];
    }

    #[DataProvider('dataFailAddJsVars')]
    public function testFailAddJsVars(string $message, array $jsVars): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        TestHelper::createWebView()->addJsVars($jsVars);
    }

    public function testTitle(): void
    {
        $view = TestHelper::createWebView();
        $view->setTitle('test');
        $this->assertSame('test', $view->getTitle());
    }

    public function testFluentSetters(): void
    {
        $view = TestHelper::createWebView();

        $this->assertSame($view, $view->setTitle(''));
        $this->assertSame($view, $view->setBlock('test', ''));
        $this->assertSame($view, $view->removeBlock('test'));
        $this->assertSame($view, $view->setParameter('test', ''));
        $this->assertSame($view, $view->setParameters([]));
        $this->assertSame($view, $view->addToParameter('test-array'));
        $this->assertSame($view, $view->removeParameter('test'));
        $this->assertSame($view, $view->registerMeta(['content' => 'utf8']));
        $this->assertSame($view, $view->registerMetaTag(Meta::tag()));
        $this->assertSame($view, $view->registerLink(['href' => '/main.css']));
        $this->assertSame($view, $view->registerLinkTag(Link::tag()));
        $this->assertSame($view, $view->registerCss('h1 { color: red; }'));
        $this->assertSame($view, $view->registerCssFromFile(__DIR__ . '/public/example.css'));
        $this->assertSame($view, $view->registerStyleTag(Style::tag()));
        $this->assertSame($view, $view->registerCssFile('./main.css'));
        $this->assertSame($view, $view->addCssFiles(['./main.css']));
        $this->assertSame($view, $view->addCssStrings(['h1 { color: red; }']));
        $this->assertSame($view, $view->registerJs('alert(42)'));
        $this->assertSame($view, $view->registerScriptTag(Script::tag()));
        $this->assertSame($view, $view->registerJsFile('./main.js'));
        $this->assertSame($view, $view->registerJsVar('test', 42));
        $this->assertSame($view, $view->addJsFiles(['./main.js']));
        $this->assertSame($view, $view->addJsStrings(['alert(1);']));
        $this->assertSame($view, $view->addJsVars(['test' => 42]));
    }

    public function testCommonStateForClonedWebViews(): void
    {
        $view = TestHelper::createWebView();
        $view->setParameter('test', 42);

        $clonedView = $view->withSourceLocale('ru');
        $clonedView->setParameter('test', 7);

        $this->assertSame(7, $view->getParameter('test'));
    }

    public function testClear(): void
    {
        $webView = TestHelper::createWebView();
        $webView->setBlock('name', 'Mike');
        $webView->setParameter('age', 42);
        $webView->setTitle('Hello, World!');
        $webView->registerMetaTag(Meta::tag());
        $webView->registerLinkTag(Link::tag());
        $webView->registerCss('h1 { color: red; }');
        $webView->registerCssFile('./main.css');
        $webView->registerJs('alert(42);');
        $webView->registerJsFile('./main.js');

        try {
            $webView->render(__DIR__ . '/public/view/error.php');
        } catch (Exception) {
        }

        $webView->clear();

        $this->assertNull($webView->getViewFile());
        $this->assertFalse($webView->hasBlock('name'));
        $this->assertFalse($webView->hasParameter('age'));
        $this->assertSame('', $webView->getTitle());

        $this->assertSame(
            '[BEGINPAGE][/BEGINPAGE]' . "\n" .
            '[HEAD][/HEAD]' . "\n" .
            '[BEGINBODY][/BEGINBODY]' . "\n" .
            '[ENDBODY][/ENDBODY]' . "\n" .
            '[ENDPAGE][/ENDPAGE]',
            $webView->render('//positions.php')
        );
    }

    public function testWithClearedState(): void
    {
        $webView = TestHelper::createWebView();
        $webView->setBlock('name', 'Mike');
        $webView->setParameter('age', 42);
        $webView->setTitle('Hello, World!');
        $webView->registerMetaTag(Meta::tag());
        $webView->registerLinkTag(Link::tag());
        $webView->registerCss('h1 { color: red; }');
        $webView->registerCssFile('./main.css');
        $webView->registerJs('alert(42);');
        $webView->registerJsFile('./main.js');

        $newWebView = $webView->withClearedState();

        $this->assertNull($newWebView->getViewFile());
        $this->assertFalse($newWebView->hasBlock('name'));
        $this->assertFalse($newWebView->hasParameter('age'));
        $this->assertSame('', $newWebView->getTitle());

        $this->assertSame(
            '[BEGINPAGE][/BEGINPAGE]' . "\n" .
            '[HEAD][/HEAD]' . "\n" .
            '[BEGINBODY][/BEGINBODY]' . "\n" .
            '[ENDBODY][/ENDBODY]' . "\n" .
            '[ENDPAGE][/ENDPAGE]',
            $newWebView->render('positions.php')
        );
    }

    public function testDeepClone(): void
    {
        $lightTheme = new Theme();
        $darkTheme = new Theme();

        $sourceView = new WebView();
        $sourceView->setTitle('Moscow');
        $sourceView->setLocale('ru');
        $sourceView->setTheme($lightTheme);

        $view = $sourceView->deepClone();
        $view->setTitle('Voronezh');
        $view->setLocale('en');
        $view->setTheme($darkTheme);

        $this->assertNotSame($view, $sourceView);
        $this->assertSame('Moscow', $sourceView->getTitle());
        $this->assertSame('ru', $sourceView->getLocale());
        $this->assertSame($lightTheme, $sourceView->getTheme());
        $this->assertSame('Voronezh', $view->getTitle());
        $this->assertSame('en', $view->getLocale());
        $this->assertSame($darkTheme, $view->getTheme());
    }

    public function testImmutability(): void
    {
        $webView = TestHelper::createWebView();

        $this->assertNotSame($webView, $webView->withClearedState());
    }
}
