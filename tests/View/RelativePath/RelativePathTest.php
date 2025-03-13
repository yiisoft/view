<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\View\RelativePath;

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
}
