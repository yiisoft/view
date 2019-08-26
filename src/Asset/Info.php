<?php
declare(strict_types = 1);

namespace Yiisoft\Asset;

final class Info
{
    public static function frameworkVersion(): string
    {
        return '3.0.0';
    }

    /**
     * Returns a value indicating whether a URL is relative.
     * A relative URL does not have host info part.
     * @param string $url the URL to be checked
     * @return bool whether the URL is relative
     */
    public static function isRelative($url)
    {
        return strncmp($url, '//', 2) && strpos($url, '://') === false;
    }
}
