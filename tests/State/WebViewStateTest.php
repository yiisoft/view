<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\State;

use PHPUnit\Framework\TestCase;
use Yiisoft\Html\Tag\Link;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\View\State\WebViewState;

final class WebViewStateTest extends TestCase
{
    public function testClear(): void
    {
        $state = new WebViewState();
        $state->setBlock('name', 'Mike');
        $state->setParameter('age', 42);
        $state->setTitle('Hello, World!');
        $state->registerMetaTag(Meta::tag());
        $state->registerLinkTag(Link::tag());
        $state->registerCss('h1 { color: red; }');
        $state->registerCssFile('./main.css');
        $state->registerJs('alert(42);');
        $state->registerJsFile('./main.js');

        $state->clear();

        $this->assertFalse($state->hasBlock('name'));
        $this->assertFalse($state->hasParameter('age'));
        $this->assertSame('', $state->getTitle());
        $this->assertSame([], $state->getMetaTags());
        $this->assertSame([], $state->getLinkTags());
        $this->assertSame([], $state->getCss());
        $this->assertSame([], $state->getCssFiles());
        $this->assertSame([], $state->getJs());
        $this->assertSame([], $state->getJsFiles());
    }
}
