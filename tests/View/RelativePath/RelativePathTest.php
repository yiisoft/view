<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\View\RelativePath;

use LogicException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Yiisoft\View\View;

final class RelativePathTest extends TestCase
{
    #[TestWith(['with-extension.php'])]
    #[TestWith(['without-extension.php'])]
    public function testParent(string $template): void
    {
        $view = new View();

        $result = $view->render(__DIR__ . '/views-parent/template/' . $template);

        $this->assertSame('THIS IS TEMPLATE. THIS IS HEADER', $result);
    }

    #[TestWith(['./template'])]
    #[TestWith(['../template'])]
    public function testRelativeWithoutCurrentView(string $template): void
    {
        $view = new View();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Unable to resolve file for view "' . $template . '": no currently rendered view.'
        );
        $view->render($template);
    }
}
