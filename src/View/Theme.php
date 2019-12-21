<?php
namespace Yiisoft\View;

use Yiisoft\Files\FileHelper;

/**
 * Theme represents an application theme.
 *
 * When [[View]] renders a view file, it will check the [[View::theme|active theme]]
 * to see if there is a themed version of the view file exists. If so, the themed version will be rendered instead.
 *
 * A theme is a directory consisting of view files which are meant to replace their non-themed counterparts.
 *
 * Theme uses [[pathMap]] to achieve the view file replacement:
 *
 * 1. It first looks for a key in [[pathMap]] that is a substring of the given view file path;
 * 2. If such a key exists, the corresponding value will be used to replace the corresponding part
 *    in the view file path;
 * 3. It will then check if the updated view file exists or not. If so, that file will be used
 *    to replace the original view file.
 * 4. If Step 2 or 3 fails, the original view file will be used.
 *
 * For example, if [[pathMap]] is `['@app/views' => '@app/themes/basic']`,
 * then the themed version for a view file `@app/views/site/index.php` will be
 * `@app/themes/basic/site/index.php`.
 *
 * It is possible to map a single path to multiple paths. For example,
 *
 * ```php
 * 'pathMap' => [
 *     '@app/views' => [
 *         '@app/themes/christmas',
 *         '@app/themes/basic',
 *     ],
 * ]
 * ```
 *
 * In this case, the themed version could be either `@app/themes/christmas/site/index.php` or
 * `@app/themes/basic/site/index.php`. The former has precedence over the latter if both files exist.
 *
 * To use a theme, you should configure the [[View::theme|theme]] property of the "view" application
 * component like the following:
 *
 * ```php
 * 'view' => [
 *     'theme' => [
 *         'basePath' => '@app/themes/basic',
 *         'baseUrl' => '@web/themes/basic',
 *     ],
 * ],
 * ```
 *
 * The above configuration specifies a theme located under the "themes/basic" directory of the Web folder
 * that contains the entry script of the application. If your theme is designed to handle modules,
 * you may configure the [[pathMap]] property like described above.
 *
 * For more details and usage information on Theme, see the [guide article on theming](guide:output-theming).
 */
class Theme
{
    /**
     * @var array the mapping between view directories and their corresponding themed versions.
     * This property is used by [[applyTo()]] when a view is trying to apply the theme.
     * [Path aliases](guide:concept-aliases) can be used when specifying directories.
     * If this property is empty or not set, a mapping [[Application::basePath]] to [[basePath]] will be used.
     */
    private array $pathMap;

    private ?string $baseUrl;

    private ?string $basePath;

    public function __construct(array $pathMap = [], string $basePath = null, string $baseUrl = null)
    {
        $this->pathMap = $pathMap;
        $this->basePath = $basePath;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * @return string the base URL (without ending slash) for this theme. All resources of this theme are considered
     * to be under this base URL.
     */
    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * @return string the root path of this theme. All resources of this theme are located under this directory.
     *
     * @see pathMap
     */
    public function getBasePath(): ?string
    {
        return $this->basePath;
    }

    /**
     * Converts a file to a themed file if possible.
     * If there is no corresponding themed file, the original file will be returned.
     *
     * @param string $path the file to be themed
     *
     * @return string the themed file, or the original file if the themed version is not available.
     */
    public function applyTo(string $path): string
    {
        if ($this->pathMap === []) {
            return $path;
        }
        $path = FileHelper::normalizePath($path);
        foreach ($this->pathMap as $from => $tos) {
            if (!is_string($from)) {
                throw new \InvalidArgumentException('Pathmap should contain strings');
            }

            $from = FileHelper::normalizePath($from) . '/';
            if (strpos($path, $from) === 0) {
                $n = strlen($from);
                foreach ((array)$tos as $to) {
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
     * Converts a relative URL into an absolute URL using [[baseUrl]].
     *
     * @param string $url the relative URL to be converted.
     *
     * @return string the absolute URL
     */
    public function getUrl(string $url): string
    {
        if (($baseUrl = $this->getBaseUrl()) !== null) {
            return $baseUrl . '/' . ltrim($url, '/');
        }

        return $url;
    }

    /**
     * Converts a relative file path into an absolute one using [[basePath]].
     *
     * @param string $path the relative file path to be converted.
     *
     * @return string the absolute file path
     */
    public function getPath(string $path): string
    {
        if (($basePath = $this->getBasePath()) !== null) {
            return $basePath . '/' . ltrim($path, '/\\');
        }

        return $path;
    }
}
