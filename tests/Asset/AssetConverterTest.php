<?php

namespace Yiisoft\Asset\Tests;

use Yiisoft\Asset\AssetConverter;
use Yiisoft\Files\FileHelper;
use Yiisoft\Tests\TestCase;

/**
 * AssetConverterTest.
 */
final class AssetConverterTest extends TestCase
{
    /**
     * @var string temporary files path
     */
    protected $tmpPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpPath = $this->aliases->get('@converter');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeAssets('@converter');
    }

    public function testConvert(): void
    {
        file_put_contents($this->tmpPath . '/test.php', <<<EOF
<?php

echo "Hello World!\n";
echo "Hello Yii!";
EOF
        );

        $converter = new AssetConverter($this->aliases);
        $converter->commands['php'] = ['txt', 'php {from} > {to}'];

        $this->assertEquals('test.txt', $converter->convert('test.php', $this->tmpPath));
        $this->assertFileExists($this->tmpPath . '/test.txt', 'Failed asserting that asset output file exists.');
        $this->assertStringEqualsFile($this->tmpPath . '/test.txt', "Hello World!\nHello Yii!");
    }

    /**
     * @depends testConvert
     */
    public function testConvertOutdated(): void
    {
        $srcFilename = $this->tmpPath . '/test.php';
        file_put_contents($srcFilename, <<<'EOF'
<?php

echo microtime();
EOF
        );

        $converter = new AssetConverter($this->aliases);
        $converter->commands['php'] = ['txt', 'php {from} > {to}'];

        $converter->convert('test.php', $this->tmpPath);
        $initialConvertTime = file_get_contents($this->tmpPath . '/test.txt');

        usleep(1);
        $converter->convert('test.php', $this->tmpPath);
        $this->assertStringEqualsFile($this->tmpPath . '/test.txt', $initialConvertTime);

        touch($srcFilename, time() + 1000);
        $converter->convert('test.php', $this->tmpPath);
        $this->assertNotEquals($initialConvertTime, file_get_contents($this->tmpPath . '/test.txt'));
    }

    /**
     * @depends testConvertOutdated
     */
    public function testForceConvert(): void
    {
        file_put_contents($this->tmpPath . '/test.php', <<<'EOF'
<?php

echo microtime();
EOF
        );

        $converter = new AssetConverter($this->aliases);
        $converter->commands['php'] = ['txt', 'php {from} > {to}'];

        $converter->convert('test.php', $this->tmpPath);
        $initialConvertTime = file_get_contents($this->tmpPath . '/test.txt');

        usleep(1);
        $converter->convert('test.php', $this->tmpPath);
        $this->assertStringEqualsFile($this->tmpPath . '/test.txt', $initialConvertTime);

        $converter->forceConvert = true;
        $converter->convert('test.php', $this->tmpPath);
        $this->assertNotEquals($initialConvertTime, file_get_contents($this->tmpPath . '/test.txt'));
    }

    /**
     * @depends testConvertOutdated
     */
    public function testCheckOutdatedCallback(): void
    {
        $srcFilename = $this->tmpPath . '/test.php';
        file_put_contents($srcFilename, <<<'EOF'
<?php

echo microtime();
EOF
        );

        $converter = new AssetConverter($this->aliases);
        $converter->commands['php'] = ['txt', 'php {from} > {to}'];

        $converter->convert('test.php', $this->tmpPath);
        $initialConvertTime = file_get_contents($this->tmpPath . '/test.txt');

        $converter->isOutdatedCallback = function () {
            return false;
        };
        $converter->convert('test.php', $this->tmpPath);
        $this->assertStringEqualsFile($this->tmpPath . '/test.txt', $initialConvertTime);

        $converter->isOutdatedCallback = function () {
            return true;
        };
        $converter->convert('test.php', $this->tmpPath);
        $this->assertNotEquals($initialConvertTime, file_get_contents($this->tmpPath . '/test.txt'));
    }

}
