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

        echo Breadcrumbs::widget()
            ->links([
                'label' => 'My Home Page', 'url' => 'http://my.example.com/yii2/link/page'
            ]);

        $actualHtml = ob_get_contents();

        $expectedHtml = "<ul class=\"breadcrumb\"><li><a href=\"/\">Home</a></li>\n" .
        "<li class=\"active\">My Home Page</li>\n" .
        "<li class=\"active\">http://my.example.com/yii2/link/page</li>\n" .
        '</ul>';

        $this->assertEquals($expectedHtml, $actualHtml);

        ob_end_clean();
    }

    public function testEmptyLinks(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo Breadcrumbs::widget();

        $actualHtml = ob_get_contents();

        $this->assertEmpty($actualHtml);

        ob_end_clean();
    }

    public function testHomeLinkFalse(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo Breadcrumbs::widget()
            ->homeLink(false)
            ->links([
                'label' => 'My Home Page',
                'url' => 'http://my.example.com/yii2/link/page'
            ]);

        $actualHtml = ob_get_contents();

        $expectedHtml = "<ul class=\"breadcrumb\"><li class=\"active\">My Home Page</li>\n" .
            "<li class=\"active\">http://my.example.com/yii2/link/page</li>\n" .
            '</ul>';

        $this->assertEquals($expectedHtml, $actualHtml);

        ob_end_clean();
    }

    public function testHomeUrlLink(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo Breadcrumbs::widget()
            ->homeLink(false)
            ->homeUrlLink(['label' => 'home-link'])
            ->links(['label' => 'My Home Page', 'url' => 'http://my.example.com/yii2/link/page']);

        $expectedHtml = "<ul class=\"breadcrumb\"><li>home-link</li>\n" .
            "<li class=\"active\">My Home Page</li>\n" .
            "<li class=\"active\">http://my.example.com/yii2/link/page</li>\n" .
            '</ul>';

        $actualHtml = ob_get_contents();

        $this->assertEquals($expectedHtml, $actualHtml);

        ob_end_clean();
    }

    public function testRenderItemException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        echo Breadcrumbs::widget()
            ->homeLink(false)
            ->links([
                'url' => 'http://my.example.com/yii2/link/page',
            ]);
    }

    public function testRenderItemLabelOnlyEncodeLabelFalse(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo Breadcrumbs::widget()
            ->activeItemTemplate("<li>{link}</li>\n")
            ->encodeLabels(false)
            ->homeLink(false)
            ->links(['label' => 'My-<br>Test-Label'])
            ->options([])
            ->tag('');

        $actualHtml = ob_get_contents();

        $this->assertEquals("<li>My-<br>Test-Label</li>\n", $actualHtml);

        ob_end_clean();
    }


    public function testRenderItemLabelOnlyEncodeLabelTrue(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo Breadcrumbs::widget()
            ->activeItemTemplate("<li>{link}</li>\n")
            ->homeLink(false)
            ->links(['label' => 'My-<br>Test-Label'])
            ->options([])
            ->tag('');

        $actualHtml = ob_get_contents();

        $this->assertEquals("<li>My-&lt;br&gt;Test-Label</li>\n", $actualHtml);

        ob_end_clean();
    }

    public function testOptions(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo Breadcrumbs::widget()
            ->homeLink(false)
            ->links(['label' => 'My Home Page', 'url' => 'http://my.example.com/yii2/link/page'])
            ->options(['class' => 'breadcrumb external']);

        $actualHtml = ob_get_contents();

        $expectedHtml = "<ul class=\"breadcrumb external\"><li class=\"active\">My Home Page</li>\n";

        $this->assertStringContainsString($expectedHtml, $actualHtml);

        ob_end_clean();
    }

    public function testTag(): void
    {
        ob_start();
        ob_implicit_flush(0);

        echo Breadcrumbs::widget()
            ->activeItemTemplate("{link}\n")
            ->itemTemplate("{link}\n")
            ->homeLink(true)
            ->links(['label' => 'My Home Page', 'url' => 'http://my.example.com/yii2/link/page'])
            ->options(['class' => 'breadcrumb'])
            ->tag('div');

        $actualHtml = ob_get_contents();

        $expectedHtml = "<div class=\"breadcrumb\"><a href=\"/\">Home</a>\n" .
            "My Home Page\n" .
            "http://my.example.com/yii2/link/page\n" .
            '</div>';

        $this->assertEquals($expectedHtml, $actualHtml);

        ob_end_clean();
    }
}
