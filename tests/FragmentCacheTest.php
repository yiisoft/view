<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use Yiisoft\Files\FileHelper;

/**
 * FragmentCacheTest.
 */
final class FragmentCacheTest extends \Yiisoft\View\Tests\TestCase
{
    /**
     * @var string path for the test files.
     */
    private string $testViewPath = '';

    public function setUp(): void
    {
        parent::setUp();

        $this->testViewPath = \sys_get_temp_dir() . '/' . \str_replace('\\', '_', \get_class($this)) . \uniqid('', false);

        FileHelper::createDirectory($this->testViewPath);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        FileHelper::removeDirectory($this->testViewPath);
    }
}
