<?php
declare(strict_types=1);

namespace Yiisoft\Widget\Tests;

use Yiisoft\Data\Reader\Sort;
use Yiisoft\Tests\TestCase;
use Yiisoft\Widget\LinkSorter;

/**
 * SpacelessTest.
 */
class LinkSorterTest extends TestCase
{
    public function testEmptyWidget(): void
    {
        $widget = LinkSorter::widget()
            ->sort(new Sort([]));

        $this->assertEquals('<ul class="sorter"></ul>', $widget->run());
    }

    public function testWidget(): void
    {
        $widget = LinkSorter::widget()
            ->attributes(['id', 'name'])
            ->sort(new Sort([]));

        $output = <<<OUTPUT
<ul class="sorter">
<li><a href="?sort=id">id</a></li>
<li><a href="?sort=name">name</a></li>
</ul>
OUTPUT;
        $this->assertEquals($output, $widget->run());
    }
}
