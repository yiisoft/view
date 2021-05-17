<?php

declare(strict_types=1);

namespace Yiisoft\View\Tests\TestSupport;

trait TestTrait
{
    /**
     * Asserting same ignoring slash.
     */
    protected function assertSameIgnoringSlash(string $expected, string $actual): void
    {
        $expected = str_replace(['/', '\\'], '/', $expected);
        $actual = str_replace(['/', '\\'], '/', $actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * Asserts that two strings equality ignoring line endings.
     */
    protected function assertEqualStringsIgnoringLineEndings(
        string $expected,
        string $actual,
        string $message = ''
    ): void {
        $expected = self::normalizeLineEndings($expected);
        $actual = self::normalizeLineEndings($actual);

        $this->assertEquals($expected, $actual, $message);
    }

    private static function normalizeLineEndings(string $value): string
    {
        return strtr($value, [
            "\r\n" => "\n",
            "\r" => "\n",
        ]);
    }
}
