<?php

namespace Yiisoft\View\Tests;

use Yiisoft\Files\FileHelper;
use Yiisoft\Tests\TestCase;
use Yiisoft\View\Theme;

/**
 * ThemeTest.
 */
final class ThemeTest extends TestCase
{
    /**
     * @var string path for the test files.
     */
    protected $testViewPath;

    public function setUp(): void
    {
        parent::setUp();
        $this->testViewPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . str_replace('\\', '_', get_class($this)) . uniqid('', false);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        FileHelper::removeDirectory($this->testViewPath);
    }

    public function testGetUrlWithoutBaseUrl(): void
    {
        $theme = new Theme([], null, null);
        $result = $theme->getUrl('/test');
        $this->assertSame('/test', $result);
    }

    public function testGetUrlWithBaseUrl(): void
    {
        $theme = new Theme([], null, 'https://yiiframework.com/');
        $result = $theme->getUrl('/test');
        $this->assertSame('https://yiiframework.com/test', $result);
    }

    public function testGetPathWithoutBasePath(): void
    {
        $theme = new Theme([], null, null);
        $result = $theme->getPath('/test');
        $this->assertSame('/test', $result);
    }

    public function testGetPathWithBasePath(): void
    {
        $theme = new Theme([], '/var/www/yiiframework.com', null);
        $result = $theme->getPath('test');
        $this->assertSame('/var/www/yiiframework.com/test', $result);
    }

    /**
     * If there is no map, return path passed
     */
    public function testApplyToWithoutMap(): void
    {
        $theme = new Theme([]);
        $path = $theme->applyTo('test');
        $this->assertSame('test', $path);
    }

    public function testApplyToBasicMapping(): void
    {
        $appPath = $this->getPath('/app/views');
        $themePath = $this->getPath('/app/themes/basic');

        $this->touch($themePath . '/test.php');

        $theme = new Theme([
            $appPath => $themePath,
        ]);

        $path = $theme->applyTo($appPath . '/test.php');
        $this->assertSameIgnoringSlash($themePath . '/test.php', $path);
    }

    /**
     * Fall back to next "to" path from the map if there is no corresponding file in the current path
     */
    public function testApplyToWithMultipleTos(): void
    {
        $appPath = $this->getPath('/app/views');
        $firstThemePath = $this->getPath('/app/themes/christmas');
        $secondThemePath = $this->getPath('/app/themes/basic');

        $this->touch($firstThemePath . '/banner.php');
        $this->touch($secondThemePath . '/test.php');

        $theme = new Theme([
            $appPath => [
                $firstThemePath,
                $secondThemePath,
            ]
        ]);

        $path = $theme->applyTo($appPath . '/test.php');
        $this->assertSameIgnoringSlash($secondThemePath . '/test.php', $path);

        $path = $theme->applyTo($appPath . '/banner.php');
        $this->assertSameIgnoringSlash($firstThemePath . '/banner.php', $path);
    }

    /**
     * If there is no file in the theme, fall back to path passed to applyTo()
     */
    public function testApplyToFallback(): void
    {
        $theme = new Theme([
            '/app/views' => '/app/themes/basic'
        ]);

        $path = $theme->applyTo('/app/views/non-existing.php');
        $this->assertSameIgnoringSlash('/app/views/non-existing.php', $path);
    }

    private function getPath(string $path): string
    {
        return $this->testViewPath . $path;
    }
}
