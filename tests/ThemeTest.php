<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Files\FileHelper;
use Yiisoft\View\Tests\TestSupport\TestHelper;
use Yiisoft\View\Tests\TestSupport\TestTrait;
use Yiisoft\View\Theme;

final class ThemeTest extends TestCase
{
    use TestTrait;

    protected string $tempDirectory;

    public function setUp(): void
    {
        parent::setUp();
        $this->tempDirectory = __DIR__ . '/public/tmp/Theme';
    }

    public function tearDown(): void
    {
        parent::tearDown();
        FileHelper::removeDirectory($this->tempDirectory);
    }

    public function testGetUrlWithoutBaseUrl(): void
    {
        $theme = new Theme([], '', '');
        $result = $theme->getUrl('/test');
        $this->assertSame('/test', $result);
    }

    public function testGetUrlWithBaseUrl(): void
    {
        $theme = new Theme([], '', 'https://yiiframework.com/');
        $result = $theme->getUrl('/test');
        $this->assertSame('https://yiiframework.com/test', $result);
    }

    public function testGetPathWithoutBasePath(): void
    {
        $theme = new Theme([], '', '');
        $result = $theme->getPath('/test');
        $this->assertSame('/test', $result);
    }

    public function testGetPathWithBasePath(): void
    {
        $theme = new Theme([], '/var/www/yiiframework.com', '');
        $result = $theme->getPath('test');
        $this->assertSame('/var/www/yiiframework.com/test', $result);
    }

    public function testGetPathWithBasePathAndSlashPrefix(): void
    {
        $theme = new Theme([], '/var/www/yiiframework.com', '');
        $result = $theme->getPath('/test');
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

        TestHelper::touch($themePath . '/test.php');

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

        TestHelper::touch($firstThemePath . '/banner.php');
        TestHelper::touch($secondThemePath . '/test.php');

        $theme = new Theme([
            $appPath => [
                $firstThemePath,
                $secondThemePath,
            ],
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
            '/app/views' => '/app/themes/basic',
        ]);

        $path = $theme->applyTo('/app/views/non-existing.php');
        $this->assertSameIgnoringSlash('/app/views/non-existing.php', $path);
    }

    public function testInvalidPathMapKey(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Theme([0 => '/app/themes/basic']);
    }

    public function testInvalidPathMapValue(): void
    {
        $this->expectExceptionMessage(
            'The path map should contain the mapping between view directories and corresponding theme directories.'
        );
        $this->expectException(InvalidArgumentException::class);

        new Theme(['/app/views' => 0]);
    }

    private function getPath(string $path): string
    {
        return $this->tempDirectory . $path;
    }
}
