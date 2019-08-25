<?php
declare(strict_types = 1);

namespace Yiisoft\Asset;

use Psr\Log\LoggerInterface;
use yii\helpers\Url;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Asset\AssetBundle;
use Yiisoft\Asset\AssetConverterInterface;
use Yiisoft\Files\FileHelper;
use YiiSoft\Html\Html;

/**
 * AssetManager manages asset bundle configuration and loading.
 *
 * AssetManager is configured in config/web.php. You can access that instance via $container->get(AssetManager::class).
 *
 * You can modify its configuration by adding an array to your application config under `components` as shown in the
 * following example:
 *
 * ```php

 * ```
 *
 * @property AssetConverterInterface $converter The asset converter. Note that the type of this property differs in
 *                                   getter and setter. See {@see getConverter()} and {@see setConverter()} for
 *                                   details.
 */
class AssetManager
{
    /**
     * @var callable a PHP callback that is called after a sub-directory or file is successfully copied. This option is
     *               used only when publishing a directory. The signature of the callback is the same as for
     *               {@see {beforeCopy}.
     *               This is passed as a parameter `afterCopy` to {@see \Yiisoft\Files\FileHelper::copyDirectory()}.
     */
    private $afterCopy;

    /**
     * Aliases component.
     *
     * @var Aliases $aliase
     */
    private $aliases;

    /**
     * directory path node_modules.
     *
     * @var array $alternatives
     */
    private $alternatives = [
        '@npm' => '@root/node_modules',
    ];

    /**
     * @var bool whether to append a timestamp to the URL of every published asset. When this is true, the URL of a
     *           published asset may look like `/path/to/asset?v=timestamp`, where `timestamp` is the last modification
     *           time of the published asset file. You normally would want to set this property to true when you have
     *           enabled HTTP caching for assets, because it allows you to bust caching when the assets are updated.
     */
    private $appendTimestamp = false;

    /**
     * @var array mapping from source asset files (keys) to target asset files (values).
     *
     * This property is provided to support fixing incorrect asset file paths in some asset bundles. When an asset
     * bundle is registered with a view, each relative asset file in its {@see AssetBundle::css|css} and
     * {@see AssetBundle::js|js} arrays will be examined against this map. If any of the keys is found to be the last
     * part of an asset file (which is prefixed with {@see {AssetBundle::sourcePath} if available), the corresponding
     * value will replace the asset and be registered with the view. For example, an asset file `my/path/to/jquery.js`
     * matches a key `jquery.js`.
     *
     * Note that the target asset files should be absolute URLs, domain relative URLs (starting from '/') or paths
     * relative to {@see baseUrl} and {@see basePath}.
     *
     * In the following example, any assets ending with `jquery.min.js` will be replaced with `jquery/dist/jquery.js`
     * which is relative to {@see baseUrl} and {@see basePath}.
     *
     * ```php
     * [
     *     'jquery.min.js' => 'jquery/dist/jquery.js',
     * ]
     * ```
     *
     * You may also use aliases while specifying map value, for example:
     *
     * ```php
     * [
     *     'jquery.min.js' => '@web/js/jquery/jquery.js',
     * ]
     * ```
     */
    private $assetMap = [];

    /**
     * @var string the root directory storing the published asset files.
     */
    private $basePath = '@public/assets';

    /**
     * @var string the base URL through which the published asset files can be accessed.
     */
    private $baseUrl = '@web/assets';

    /**
     * @var callable a PHP callback that is called before copying each sub-directory or file. This option is used only
     *               when publishing a directory. If the callback returns false, the copy operation for the
     *               sub-directory or file will be cancelled.
     *
     * The signature of the callback should be: `function ($from, $to)`, where `$from` is the sub-directory or file to
     * be copied from, while `$to` is the copy target.
     *
     * This is passed as a parameter `beforeCopy` to {@see Yiisoft\Files\FileHelper::copyDirectory()}.
     */
    private $beforeCopy;

    /**
     * @var array|bool list of asset bundle configurations. This property is provided to customize asset bundles.
     *                 When a bundle is being loaded by {@see getBundle()}, if it has a corresponding configuration
     *                 specified here, the configuration will be applied to the bundle.
     *
     * The array keys are the asset bundle names, which typically are asset bundle class names without leading
     * backslash. The array values are the corresponding configurations. If a value is false, it means the corresponding
     * asset bundle is disabled and {@see getBundle()} should return null.
     *
     * If this property is false, it means the whole asset bundle feature is disabled and {@see {getBundle()} will
     * always return null.
     *
     * The following example shows how to disable the bootstrap css file used by Bootstrap widgets (because you want to
     * use your own styles):
     *
     * ```php
     * [
     *     \Yiisoft\Bootstrap4\BootstrapAsset::class => [
     *         'css' => [],
     *     ],
     * ]
     * ```
     */
    private $bundles = [];

    /**
     * AssetConverter component.
     *
     * @var AssetConverterInterface $converter
     */
    private $converter;

    /**
     * @var int the permission to be set for newly generated asset directories. This value will be used by PHP chmod()
     *          function. No umask will be applied. Defaults to 0775, meaning the directory is read-writable by owner
     *          and group, but read-only for other users.
     */
    private $dirMode = 0775;

    /**
     * @var AssetBundle $dummyBundles
     */
    private $dummyBundles;

    /**
     * @var int the permission to be set for newly published asset files. This value will be used by PHP chmod()
     *          function. No umask will be applied. If not set, the permission will be determined by the current
     *          environment.
     */
    private $fileMode;

    /**
     * @var bool whether the directory being published should be copied even if it is found in the target directory.
     *           This option is used only when publishing a directory. You may want to set this to be `true` during the
     *           development stage to make sure the published directory is always up-to-date. Do not set this to true
     *           on production servers as it will significantly degrade the performance.
     */
    private $forceCopy = false;

    /**
     * @var callable a callback that will be called to produce hash for asset directory generation. The signature of the
     *               callback should be as follows:
     *
     * ```
     * function ($path)
     * ```
     *
     * where `$path` is the asset path. Note that the `$path` can be either directory where the asset files reside or a
     * single file. For a CSS file that uses relative path in `url()`, the hash implementation should use the directory
     * path of the file instead of the file path to include the relative asset files in the copying.
     *
     * If this is not set, the asset manager will use the default CRC32 and filemtime in the `hash` method.
     *
     * Example of an implementation using MD4 hash:
     *
     * ```php
     * function ($path) {
     *     return hash('md4', $path);
     * }
     * ```
     */
    private $hashCallback;

    /**
     * @var bool whether to use symbolic link to publish asset files. Defaults to false, meaning asset files are copied
     *           to {@see basePath}. Using symbolic links has the benefit that the published assets will always be
     *           consistent with the source assets and there is no copy operation required. This is especially useful
     *           during development.
     *
     * However, there are special requirements for hosting environments in order to use symbolic links. In particular,
     * symbolic links are supported only on Linux/Unix, and Windows Vista/2008 or greater.
     *
     * Moreover, some Web servers need to be properly configured so that the linked assets are accessible to Web users.
     * For example, for Apache Web server, the following configuration directive should be added for the Web folder:
     *
     * ```apache
     * Options FollowSymLinks
     * ```
     */
    private $linkAssets = false;

    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    /**
     * @var array published assets
     */
    private $published = [];

    /**
     * @var string $realBasePath
     */
    private $realBasePath;

    /**
     * AssetManager constructor.
     *
     * @param Aliases $aliases
     */
    public function __construct(Aliases $aliases, LoggerInterface $logger)
    {
        $this->aliases = $aliases;
        $this->logger = $logger;
        $this->setDefaultPaths();
    }

    /**
     * Returns the actual URL for the specified asset.
     * The actual URL is obtained by prepending either {@see AssetBundle::$baseUrl} or {@see AssetManager::$baseUrl} to
     * the given asset path.
     *
     * @param AssetBundle $bundle the asset bundle which the asset file belongs to
     * @param string      $asset the asset path. This should be one of the assets listed in {@see AssetBundle::$js} or
     *                    {@see AssetBundle::$css}.
     *
     * @return string the actual URL for the specified asset.
     */
    public function getAssetUrl(AssetBundle $bundle, string $asset): string
    {
        if (($actualAsset = $this->resolveAsset($bundle, $asset)) !== false) {
            if (strncmp((string) $actualAsset, '@web/', 5) === 0) {
                $asset = substr((string) $actualAsset, 5);
                $basePath = $this->aliases->get('@public');
                $baseUrl = $this->aliases->get('@web');
            } else {
                $asset = $this->aliases->get($actualAsset);
                $basePath = $this->getRealBasePath();
                $baseUrl = $this->baseUrl;
            }
        } else {
            $basePath = $this->aliases->get($bundle->basePath);
            $baseUrl = $this->aliases->get($bundle->baseUrl);
        }

        if (!Url::isRelative($asset) || strncmp($asset, '/', 1) === 0) {
            return $asset;
        }

        if ($this->appendTimestamp && ($timestamp = @filemtime("$basePath/$asset")) > 0) {
            return "$baseUrl/$asset?v=$timestamp";
        }

        return "$baseUrl/$asset";
    }

    /**
     * Returns the actual file path for the specified asset.
     *
     * @param AssetBundle $bundle the asset bundle which the asset file belongs to
     * @param string      $asset  the asset path. This should be one of the assets listed in {@see AssetBundle::$js} or
     *                    {@see AssetBundle::$css}.
     *
     * @return false|string the actual file path, or `false` if the asset is specified as an absolute URL
     */
    public function getAssetPath(AssetBundle $bundle, string $asset)
    {
        if (($actualAsset = $this->resolveAsset($bundle, $asset)) !== false) {
            return Url::isRelative((string) $actualAsset) ? $this->getRealBasePath() . '/' . $actualAsset : false;
        }

        return Url::isRelative($asset) ? $bundle->basePath . '/' .$asset : false;
    }

    /**
     * Returns the named asset bundle.
     *
     * This method will first look for the bundle in {@see bundles()}. If not found, it will treat `$name` as the class
     * of the asset bundle and create a new instance of it.
     *
     * @param string $name    the class name of the asset bundle (without the leading backslash)
     * @param bool   $publish whether to publish the asset files in the asset bundle before it is returned. If you set
     *                        this false, you must manually call `AssetBundle::publish()` to publish the asset files.
     *
     * @return AssetBundle the asset bundle instance
     *
     * @throws \InvalidArgumentException
     */
    public function getBundle(string $name, bool $publish = true): AssetBundle
    {
        if ($this->bundles === false) {
            return $this->loadDummyBundle($name);
        }

        if (!isset($this->bundles[$name])) {
            return $this->bundles[$name] = $this->loadBundle($name, [], $publish);
        }

        if ($this->bundles[$name] instanceof AssetBundle) {
            return $this->bundles[$name];
        }

        if (is_array($this->bundles[$name])) {
            return $this->bundles[$name] = $this->loadBundle($name, $this->bundles[$name], $publish);
        }

        if ($this->bundles[$name] === false) {
            return $this->loadDummyBundle($name);
        }

        throw new \InvalidArgumentException("Invalid asset bundle configuration: $name");
    }

    /**
     * Returns the asset converter.
     *
     * @return AssetConverterInterface the asset converter.
     */
    public function getConverter(): AssetConverterInterface
    {
        if ($this->converter === null) {
            $this->converter = new AssetConverter($this->aliases, $this->logger);
        } elseif (is_array($this->converter) || is_string($this->converter)) {
            if (is_array($this->converter) && !isset($this->converter['__class'])) {
                $this->converter['__class'] = AssetConverter::class;
            }
            $this->converter = new $this->converter($this->aliases, $this->logger);
        }

        return $this->converter;
    }

    /**
     * Returns the published path of a file path.
     *
     * This method does not perform any publishing. It merely tells you if the file or directory is published, where it
     * will go.
     *
     * @param string $path directory or file path being published
     *
     * @return string|bool string the published file path. False if the file or directory does not exist
     */
    public function getPublishedPath(string $path)
    {
        $path = $this->aliases->get($path);

        if (isset($this->published[$path])) {
            return $this->published[$path][0];
        }
        if (is_string($path) && ($path = realpath($path)) !== false) {
            return $this->getRealBasePath() . DIRECTORY_SEPARATOR . $this->hash($path) . (is_file($path) ?
                   DIRECTORY_SEPARATOR . basename($path) : '');
        }

        return false;
    }

    /**
     * Returns the URL of a published file path.
     *
     * This method does not perform any publishing. It merely tells you if the file path is published, what the URL will
     * be to access it.
     *
     * @param string $path directory or file path being published
     *
     * @return string|bool string the published URL for the file or directory. False if the file or directory does not
     *                     exist.
     */
    public function getPublishedUrl(string $path)
    {
        if (isset($this->published[$path])) {
            return $this->published[$path][1];
        }
        if (is_string($path) && ($path = realpath($path)) !== false) {
            return $this->baseUrl.'/'.$this->hash($path).(is_file($path) ? '/'.basename($path) : '');
        }

        return false;
    }

    /**
     * Get RealBasePath.
     *
     * @return bool|string
     */
    public function getRealBasePath()
    {
        if ($this->realBasePath === null) {
            $this->realBasePath = (string) $this->prepareBasePath($this->basePath);
        }

        return $this->realBasePath;
    }

    /**
     * prepareBasePath
     *
     * @param string $basePath
     *
     * @throws \InvalidArgumentException
     *
     * @return string|bool
     */
    public function prepareBasePath(string $basePath)
    {
        $basePath = $this->aliases->get($basePath);

        if (!is_dir($basePath)) {
            throw new \InvalidArgumentException("The directory does not exist: {$basePath}");
        }

        if (!is_writable($basePath)) {
            throw new \InvalidArgumentException("The directory is not writable by the Web process: {$basePath}");
        }

        return realpath($basePath);
    }

    /**
     * Publishes a file or a directory.
     *
     * This method will copy the specified file or directory to {@see basePath} so that it can be accessed via the Web
     * server.
     *
     * If the asset is a file, its file modification time will be checked to avoid unnecessary file copying.
     *
     * If the asset is a directory, all files and subdirectories under it will be published recursively. Note, in case
     * $forceCopy is false the method only checks the existence of the target directory to avoid repetitive copying
     * (which is very expensive).
     *
     * By default, when publishing a directory, subdirectories and files whose name starts with a dot "." will NOT be
     * published. If you want to change this behavior, you may specify the "beforeCopy" option as explained in the
     * `$options` parameter.
     *
     * Note: On rare scenario, a race condition can develop that will lead to a  one-time-manifestation of a
     * non-critical problem in the creation of the directory that holds the published assets. This problem can be
     * avoided altogether by 'requesting' in advance all the resources that are supposed to trigger a 'publish()' call,
     * and doing that in the application deployment phase, before system goes live. See more in the following
     * discussion: http://code.google.com/p/yii/issues/detail?id=2579
     *
     * @param string $path    the asset (file or directory) to be published
     * @param array  $options the options to be applied when publishing a directory. The following options are
     *               supported:
     *
     * - only: array, list of patterns that the file paths should match if they want to be copied.
     * - except: array, list of patterns that the files or directories should match if they want to be excluded from
     *   being copied.
     * - caseSensitive: boolean, whether patterns specified at "only" or "except" should be case sensitive. Defaults to
     *   true.
     * - beforeCopy: callback, a PHP callback that is called before copying each sub-directory or file.
     *   This overrides {@see beforeCopy} if set.
     * - afterCopy: callback, a PHP callback that is called after a sub-directory or file is successfully copied. This
     *   overrides {@seee afterCopy} if set.
     * - forceCopy: boolean, whether the directory being published should be copied even if it is found in the target
     *   directory. This option is used only when publishing a directory. This overrides {@see forceCopy} if set.
     *
     * @throws \InvalidArgumentException if the asset to be published does not exist.
     *
     * @return array the path (directory or file path) and the URL that the asset is published as.
     */
    public function publish(string $path, array $options = []): array
    {
        $path = $this->aliases->get($path);

        if (isset($this->published[$path])) {
            return $this->published[$path];
        }

        if (!is_string($path) || ($src = realpath($path)) === false) {
            throw new \InvalidArgumentException("The file or directory to be published does not exist: $path");
        }

        if (is_file($src)) {
            return $this->published[$path] = $this->publishFile($src);
        }

        return $this->published[$path] = $this->publishDirectory($src, $options);
    }

    /**
     * Set afterCopy.
     *
     * @param callable $value
     *
     * @return void
     *
     * {@see afterCopy}
     */
    public function setAfterCopy(callable $value): void
    {
        $this->afterCopy = $value;
    }

    /**
     * Set alternatives.
     *
     * @param array $value
     *
     * @return void
     *
     * {@see alternatives}
     */
    public function setAlternatives(array $value): void
    {
        $this->alternatives = $value;
        $this->setAlternativesAlias();
    }

    /**
     * Set appendTimestamp.
     *
     * @param bool $value
     *
     * @return void
     *
     * {@see appendTimestamp}
     */
    public function setAppendTimestamp(bool $value): void
    {
        $this->appendTimestamp = $value;
    }

    /**
     * Set assetMap.
     *
     * @param array $value
     *
     * @return void
     *
     * {@see assetMap}
     */
    public function setAssetMap(array $value): void
    {
        $this->assetMap = $value;
    }

    /**
     * Set basePath.
     *
     * @param string $value
     *
     * @return void
     *
     * {@see basePath}
     */
    public function setBasePath(string $value): void
    {
        $this->basePath = $value;
    }

    /**
     * Set baseUrl.
     *
     * @param string $value
     *
     * @return void
     *
     * {@see baseUrl}
     */
    public function setBaseUrl(string $value): void
    {
        $this->baseUrl = $value;
    }

    /**
     * Set beforeCopy.
     *
     * @param callable $value
     *
     * @return void
     *
     * {@see beforeCopy}
     */
    public function setBeforeCopy(callable $value): void
    {
        $this->beforeCopy = $value;
    }

    /**
     * Set bundles.
     *
     * @param array $value
     *
     * @return void
     *
     * {@see beforeCopy}
     */
    public function setBundles(array $value): void
    {
        $this->bundles = $value;
    }

    /**
     * Sets the asset converter.
     *
     * @param AssetConverterInterface $value the asset converter. This can be eitheran object implementing the
     *                                      {@see AssetConverterInterface}, or a configuration array that can be used
     *                                      to create the asset converter object.
     */
    public function setConverter(AssetConverterInterface $value): void
    {
        $this->converter = $value;
    }

    /**
     * Set dirMode.
     *
     * @param int $value
     *
     * @return void
     *
     * {@see dirMode}
     */
    public function setDirMode(int $value): void
    {
        $this->dirMode = $value;
    }

    /**
     * Set fileMode.
     *
     * @param int $value
     *
     * @return void
     *
     * {@see fileMode}
     */
    public function setFileMode(int $value): void
    {
        $this->fileMode = $value;
    }

    /**
     * Set hashCallback.
     *
     * @param callable $value
     *
     * @return void
     *
     * {@see hashCallback}
     */
    public function setHashCallback(callable $value): void
    {
        $this->hashCallback = $value;
    }

    /**
     * Set linkAssets.
     *
     * @param bool $value
     *
     * @return void
     *
     * {@see linkAssets}
     */
    public function setLinkAssets(bool $value): void
    {
        $this->linkAssets = $value;
    }

    /**
     * Returns a string representing the current version of the Yii framework.
     * @return string the version of Yii framework
     */
    protected static function getVersion()
    {
        return '3.0-dev';
    }

    /**
     * Generate a CRC32 hash for the directory path. Collisions are higher than MD5 but generates a much smaller hash
     * string.
     *
     * @param string $path string to be hashed.
     *
     * @return string hashed string.
     */
    protected function hash(string $path): string
    {
        if (is_callable($this->hashCallback)) {
            return call_user_func($this->hashCallback, $path);
        }
        $path = (is_file($path) ? dirname($path) : $path).filemtime($path);

        return sprintf('%x', crc32($path . static::getVersion() . '|' . $this->linkAssets));
    }

    /**
     * Loads asset bundle class by name.
     *
     * @param string $name    bundle name
     * @param array  $config  bundle object configuration
     * @param bool   $publish if bundle should be published
     *
     * @return AssetBundle
     */
    protected function loadBundle(string $name, array $config = [], bool $publish = true): AssetBundle
    {
        if (!isset($config['__class'])) {
            $config['__class'] = $name;
        }
        /* @var $bundle AssetBundle */
        $bundle = new $config['__class']();

        if ($publish) {
            $bundle->publish($this);
        }

        return $bundle;
    }

    /**
     * Loads dummy bundle by name.
     *
     * @param string $name
     *
     * @return AssetBundle
     */
    protected function loadDummyBundle(string $name): AssetBundle
    {
        if (!isset($this->dummyBundles[$name])) {
            $this->dummyBundles[$name] = $this->loadBundle($name, [
                'sourcePath' => null,
                'js'         => [],
                'css'        => [],
                'depends'    => [],
            ]);
        }

        return $this->dummyBundles[$name];
    }

    /**
     * Publishes a file.
     *
     * @param string $src the asset file to be published
     *
     * @throws \Exception if the asset to be published does not exist.
     *
     * @return array the path and the URL that the asset is published as.
     */
    protected function publishFile(string $src): array
    {
        $dir = $this->hash($src);
        $fileName = basename($src);
        $dstDir = $this->getRealBasePath() .DIRECTORY_SEPARATOR . $dir;
        $dstFile = $dstDir . DIRECTORY_SEPARATOR . $fileName;

        if (!is_dir($dstDir)) {
            FileHelper::createDirectory($dstDir, $this->dirMode);
        }

        if ($this->linkAssets) {
            if (!is_file($dstFile)) {
                try { // fix #6226 symlinking multi threaded
                    symlink($src, $dstFile);
                } catch (\Exception $e) {
                    if (!is_file($dstFile)) {
                        throw $e;
                    }
                }
            }
        } elseif (@filemtime($dstFile) < @filemtime($src)) {
            copy($src, $dstFile);
            if ($this->fileMode !== null) {
                @chmod($dstFile, $this->fileMode);
            }
        }

        return [$dstFile, $this->baseUrl . "/$dir/$fileName"];
    }

    /**
     * Publishes a directory.
     *
     * @param string $src     the asset directory to be published
     * @param array  $options the options to be applied when publishing a directory. The following options are
     *                        supported:
     *
     * - only: array, list of patterns that the file paths should match if they want to be copied.
     * - except: array, list of patterns that the files or directories should match if they want to be excluded from
     *   being copied.
     * - caseSensitive: boolean, whether patterns specified at "only" or "except" should be case sensitive. Defaults
     *   to true.
     * - beforeCopy: callback, a PHP callback that is called before copying each sub-directory or file. This overrides
     *   {@see beforeCopy} if set.
     * - afterCopy: callback, a PHP callback that is called after a sub-directory or file is successfully copied. This
     *   overrides {@see afterCopy} if set.
     * - forceCopy: boolean, whether the directory being published should be copied even if it is found in the target
     *   directory. This option is used only when publishing a directory. This overrides {@see forceCopy} if set.
     *
     * @throws \Exception if the asset to be published does not exist.
     *
     * @return array the path directory and the URL that the asset is published as.
     */
    protected function publishDirectory(string $src, array $options): array
    {
        $dir = $this->hash($src);
        $dstDir = $this->getRealBasePath() . DIRECTORY_SEPARATOR . $dir;

        if ($this->linkAssets) {
            if (!is_dir($dstDir)) {
                FileHelper::createDirectory(dirname($dstDir), $this->dirMode);

                try { // fix #6226 symlinking multi threaded
                    symlink($src, $dstDir);
                } catch (\Exception $e) {
                    if (!is_dir($dstDir)) {
                        throw $e;
                    }
                }
            }
        } elseif (!empty($options['forceCopy']) || ($this->forceCopy && !isset($options['forceCopy'])) || !is_dir($dstDir)) {
            $opts = array_merge(
                $options,
                [
                    'dirMode'              => $this->dirMode,
                    'fileMode'             => $this->fileMode,
                    'copyEmptyDirectories' => false,
                ]
            );

            if (!isset($opts['beforeCopy'])) {
                if ($this->beforeCopy !== null) {
                    $opts['beforeCopy'] = $this->beforeCopy;
                } else {
                    $opts['beforeCopy'] = function ($from, $to) {
                        return strncmp(basename($from), '.', 1) !== 0;
                    };
                }
            }

            if (!isset($opts['afterCopy']) && $this->afterCopy !== null) {
                $opts['afterCopy'] = $this->afterCopy;
            }

            FileHelper::copyDirectory($src, $dstDir, $opts);
        }


        return [$dstDir, $this->baseUrl.'/'.$dir];
    }

    /**
     * @param AssetBundle $bundle
     * @param string      $asset
     *
     * @return string|bool
     */
    protected function resolveAsset(AssetBundle $bundle, string $asset)
    {
        if (isset($this->assetMap[$asset])) {
            return $this->assetMap[$asset];
        }

        if ($bundle->sourcePath !== null && Url::isRelative($asset)) {
            $asset = $bundle->sourcePath . '/' . $asset;
        }

        $n = mb_strlen($asset, 'utf-8');

        foreach ($this->assetMap as $from => $to) {
            $n2 = mb_strlen($from, 'utf-8');
            if ($n2 <= $n && substr_compare($asset, $from, $n - $n2, $n2) === 0) {
                return $to;
            }
        }

        return false;
    }

    /**
     * Set default paths asset manager.
     *
     * @return void
     */
    private function setDefaultPaths(): void
    {
        $this->setAlternativesAlias();

        $this->basePath = $this->aliases->get($this->basePath);

        if (!is_dir($this->basePath)) {
            throw new \InvalidArgumentException("The directory does not exist: {$this->basePath}");
        }

        $this->basePath = (string) realpath($this->basePath);
        $this->baseUrl = rtrim($this->aliases->get($this->baseUrl), '/');
    }

    /**
     * Set alternatives aliases.
     *
     * @return void
     */
    protected function setAlternativesAlias(): void
    {
        foreach ($this->alternatives as $alias => $path) {
            $this->aliases->set($alias, $path);
        }
    }
}
