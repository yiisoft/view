<?php

declare(strict_types=1);

namespace Yiisoft\View;

use InvalidArgumentException;
use Yiisoft\Files\FileHelper;

use function is_file;
use function is_string;
use function ltrim;
use function rtrim;
use function strlen;
use function strpos;
use function substr;

/**
 * `Theme` represents an application theme.
 *
 * When {@see View} renders a view file, it will check the {@see View::$theme} to see if there is a themed
 * version of the view file exists. If so, the themed version will be rendered instead.
 *
 * A theme is a directory consisting of view files which are meant to replace their non-themed counterparts.
 *
 * Theme uses {@see Theme::$pathMap} to achieve the view file replacement:
 *
 * 1. It first looks for a key in {@see Theme::$pathMap} that is a substring of the given view file path;
 * 2. If such a key exists, the corresponding value will be used to replace the corresponding part
 *    in the view file path;
 * 3. It will then check if the updated view file exists or not. If so, that file will be used
 *    to replace the original view file.
 * 4. If Step 2 or 3 fails, the original view file will be used.
 *
 * For example, if {@see Theme::$pathMap} is `['/app/views' => '/app/themes/basic']`, then the themed version for
 * a view file `/app/views/site/index.php` will be `/app/themes/basic/site/index.php`.
 *
 * It is possible to map a single path to multiple paths. For example:
 *
 * ```php
 * 'yiisoft/view' => [
 *     'theme' => [
 *         'pathMap' => [
 *             '/app/views' => [
 *                 '/app/themes/christmas',
 *                 '/app/themes/basic',
 *             ],
 *         ],
 *         'basePath' => '',
 *         'baseUrl' => '',
 *     ],
 * ],
 * ```
 *
 * In this case, the themed version could be either `/app/themes/christmas/site/index.php` or
 * `/app/themes/basic/site/index.php`. The former has precedence over the latter if both files exist.
 *
 * To use the theme directly without configurations, you should set it using the {@see View::setTheme()} as follows:
 *
 * ```php
 * $pathMap = [...];
 * $basePath = '/path/to/private/themes/basic';
 * $baseUrl = '/path/to/public/themes/basic';
 *
 * $view->setTheme(new Theme([...], $basePath, $baseUrl));
 * ```
 */
final class Theme
{
    /**
     * @var array<string, string|string[]>
     */
    private array $pathMap;
    private string $basePath = '';
    private string $baseUrl = '';

    /**
     * @param array $pathMap The mapping between view directories and their corresponding
     * themed versions. The path map is used by {@see applyTo()} when a view is trying to apply the theme.
     * @param string $basePath The base path to the theme directory.
     * @param string $baseUrl The base URL for this theme.
     *
     * @psalm-param array<string, string|string[]> $pathMap
     */
    public function __construct(array $pathMap = [], string $basePath = '', string $baseUrl = '')
    {
        $this->validatePathMap($pathMap);
        $this->pathMap = $pathMap;

        if ($basePath !== '') {
            $this->basePath = rtrim($basePath, '/');
        }

        if ($baseUrl !== '') {
            $this->baseUrl = rtrim($baseUrl, '/');
        }
    }

    /**
     * Returns the URL path for this theme.
     *
     * @return string The base URL (without ending slash) for this theme. All resources of this theme are considered
     * to be under this base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Returns the base path to the theme directory.
     *
     * @return string The root path of this theme. All resources of this theme are located under this directory.
     *
     * @see pathMap
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Converts a file to a themed file if possible.
     *
     * If there is no corresponding themed file, the original file will be returned.
     *
     * @param string $path The file to be themed
     *
     * @return string The themed file, or the original file if the themed version is not available.
     */
    public function applyTo(string $path): string
    {
        if ($this->pathMap === []) {
            return $path;
        }

        $path = FileHelper::normalizePath($path);

        foreach ($this->pathMap as $from => $tos) {
            $from = FileHelper::normalizePath($from) . '/';

            if (strpos($path, $from) === 0) {
                $n = strlen($from);

                foreach ((array) $tos as $to) {
                    $to = FileHelper::normalizePath($to) . '/';
                    $file = $to . substr($path, $n);

                    if (is_file($file)) {
                        return $file;
                    }
                }
            }
        }

        return $path;
    }

    /**
     * Converts and returns a relative URL into an absolute URL using {@see getbaseUrl()}.
     *
     * @param string $url The relative URL to be converted.
     *
     * @return string The absolute URL
     */
    public function getUrl(string $url): string
    {
        if (($baseUrl = $this->getBaseUrl()) !== '') {
            return $baseUrl . '/' . ltrim($url, '/');
        }

        return $url;
    }

    /**
     * Converts and returns a relative file path into an absolute one using {@see getBasePath()}.
     *
     * @param string $path The relative file path to be converted.
     *
     * @return string The absolute file path.
     */
    public function getPath(string $path): string
    {
        if (($basePath = $this->getBasePath()) !== '') {
            return $basePath . '/' . ltrim($path, '/\\');
        }

        return $path;
    }

    /**
     * Validates the path map.
     *
     * @param array $pathMap The path map for validation.
     */
    private function validatePathMap(array $pathMap): void
    {
        /** @var mixed $destinations */
        foreach ($pathMap as $source => $destinations) {
            if (!is_string($source)) {
                $this->throwInvalidPathMapException();
            }

            /** @var mixed $destination */
            foreach ((array)$destinations as $destination) {
                if (!is_string($destination)) {
                    $this->throwInvalidPathMapException();
                }
            }
        }
    }

    private function throwInvalidPathMapException(): void
    {
        throw new InvalidArgumentException(
            'The path map should contain the mapping between view directories and corresponding theme directories.'
        );
    }
}
