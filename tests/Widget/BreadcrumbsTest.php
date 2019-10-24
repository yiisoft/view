<?php
declare(strict_types = 1);

namespace Yiisoft\Widget\Tests;

use Yiisoft\Tests\TestCase;
use Yiisoft\Widget\Breadcrumbs;

/**
 * BreadcrumbsTest.
 */
class BreadcrumbsTest extends TestCase
{
    public function testHomeLinkTrue(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo (new Breadcrumbs($this->webView))
            ->links([
                'label' => 'My Home Page', 'url' => 'http://my.example.com/yii2/link/page'
            ]);

        $actualHtml = ob_get_clean();

        $expectedHtml = "<ul class=\"breadcrumb\"><li><a href=\"/\">Home</a></li>\n" .
        "<li class=\"active\">My Home Page</li>\n" .
        "<li class=\"active\">http://my.example.com/yii2/link/page</li>\n" .
        '</ul>';

        $this->assertEquals($expectedHtml, $actualHtml);
    }

    public function testEmptyLinks(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo (new Breadcrumbs($this->webView));

        $actualHtml = ob_get_clean();

        $this->assertEmpty($actualHtml);
    }

    public function testHomeLinkFalse(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo (new Breadcrumbs($this->webView))
            ->homeLink(false)
            ->links([
                'label' => 'My Home Page',
                'url' => 'http://my.example.com/yii2/link/page'
            ]);

        $actualHtml = ob_get_clean();

        $expectedHtml = "<ul class=\"breadcrumb\"><li class=\"active\">My Home Page</li>\n" .
            "<li class=\"active\">http://my.example.com/yii2/link/page</li>\n" .
            '</ul>';

        $this->assertEquals($expectedHtml, $actualHtml);
    }

    public function testHomeUrlLink(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo (new Breadcrumbs($this->webView))
            ->homeLink(false)
            ->homeUrlLink(['label' => 'home-link'])
            ->links(['label' => 'My Home Page', 'url' => 'http://my.example.com/yii2/link/page']);

        $expectedHtml = "<ul class=\"breadcrumb\"><li>home-link</li>\n" .
            "<li class=\"active\">My Home Page</li>\n" .
            "<li class=\"active\">http://my.example.com/yii2/link/page</li>\n" .
            '</ul>';

        $actualHtml = ob_get_clean();

        $this->assertEquals($expectedHtml, $actualHtml);
    }

    public function testRenderItemException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        echo (new Breadcrumbs($this->webView))
            ->homeLink(false)
            ->links([
                'url' => 'http://my.example.com/yii2/link/page',
            ]);
    }

    public function testRenderItemLabelOnlyEncodeLabelFalse(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo (new Breadcrumbs($this->webView))
            ->activeItemTemplate("<li>{link}</li>\n")
            ->encodeLabels(false)
            ->homeLink(false)
            ->links(['label' => 'My-<br>Test-Label'])
            ->options([])
            ->tag('');

        $actualHtml = ob_get_clean();

        $this->assertEquals("<li>My-<br>Test-Label</li>\n", $actualHtml);
    }


    public function testRenderItemLabelOnlyEncodeLabelTrue(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo (new Breadcrumbs($this->webView))
            ->activeItemTemplate("<li>{link}</li>\n")
            ->homeLink(false)
            ->links(['label' => 'My-<br>Test-Label'])
            ->options([])
            ->tag('');

        $actualHtml = ob_get_clean();

        $this->assertEquals("<li>My-&lt;br&gt;Test-Label</li>\n", $actualHtml);
    }

    public function testOptions(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo (new Breadcrumbs($this->webView))
            ->homeLink(false)
            ->links(['label' => 'My Home Page', 'url' => 'http://my.example.com/yii2/link/page'])
            ->options(['class' => 'breadcrumb external']);

        $actualHtml = ob_get_clean();

        $expectedHtml = "<ul class=\"breadcrumb external\"><li class=\"active\">My Home Page</li>\n";

        $this->assertStringContainsString($expectedHtml, $actualHtml);
    }

    public function testTag(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo (new Breadcrumbs($this->webView))
            ->activeItemTemplate("{link}\n")
            ->itemTemplate("{link}\n")
            ->homeLink(true)
            ->links(['label' => 'My Home Page', 'url' => 'http://my.example.com/yii2/link/page'])
            ->options(['class' => 'breadcrumb'])
            ->tag('div');

        $actualHtml = ob_get_clean();

        $expectedHtml = "<div class=\"breadcrumb\"><a href=\"/\">Home</a>\n" .
            "My Home Page\n" .
            "http://my.example.com/yii2/link/page\n" .
            '</div>';

        $this->assertEquals($expectedHtml, $actualHtml);
    }
}
