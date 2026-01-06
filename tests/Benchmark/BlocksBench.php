<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\Benchmark;

use Yiisoft\Files\FileHelper;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\View\View;

final class BlocksBench
{
    private readonly View $view;

    public function __construct()
    {
        $basePath = __DIR__ . '/../public/tmp/blocks-bench';
        FileHelper::ensureDirectory($basePath);

        $contentView = $basePath . '/content.php';
        if (!is_file($contentView)) {
            file_put_contents(
                $contentView,
                <<<'PHP'
<?php

declare(strict_types=1);

/** @var \Yiisoft\View\View $this */

$this->setBlock('block-id-1', '...content of block1...');
$this->setBlock('block-id-2', '...content of block2...');
PHP
            );
        }

        $layoutView = $basePath . '/layout.php';
        if (!is_file($layoutView)) {
            file_put_contents(
                $layoutView,
                <<<'PHP'
<?php

declare(strict_types=1);

/** @var \Yiisoft\View\View $this */
?>
<?php if ($this->hasBlock('block-id-1')): ?>
    <?= $this->getBlock('block-id-1') ?>
<?php else: ?>
    ... default content for block1 ...
<?php endif; ?>

<?php if ($this->hasBlock('block-id-2')): ?>
    <?= $this->getBlock('block-id-2') ?>
<?php else: ?>
    ... default content for block2 ...
<?php endif; ?>
PHP
            );
        }

        $this->view = new View($basePath, new SimpleEventDispatcher());
    }

    public function benchRenderBlocksWithTemplates(): void
    {
        // Render content view to define blocks.
        $this->view->render('content');

        // Render layout view which reads the defined blocks.
        $this->view->render('layout');
    }
}
