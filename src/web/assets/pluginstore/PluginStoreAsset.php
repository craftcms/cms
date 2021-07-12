<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\pluginstore;

use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;
use yii\caching\TagDependency;
use yii\web\AssetBundle;
use yii\web\NotFoundHttpException;

/**
 * Asset bundle for the Plugin Store page
 */
class PluginStoreAsset extends AssetBundle
{
    const CACHE_KEY = 'pluginstore';
    const CACHE_TAG = 'pluginstore';

    const DEVMODE_CACHE_DURATION = 1;

    /**
     * @var array
     */
    private $files = [];

    /**
     * @var bool
     */
    private $isHot = false;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist/';

        $this->depends = [
            CpAsset::class,
            VueAsset::class,
        ];

        $pluginStoreService = Craft::$app->getPluginStore();

        $config = [
            'devServer' => [
                'manifestPath' => $pluginStoreService->devServerManifestPath,
                'publicPath' => $pluginStoreService->devServerPublicPath,
            ],
            'server' => [
                'manifestPath' => __DIR__ . '/dist/',
                'publicPath' => '',
            ],
            'manifest' => [
                'legacy' => 'manifest.json',
                'modern' => 'manifest.json',
            ],
        ];

        $this->css = [
            $this->getModule($config, 'chunk-vendors.css'),
            $this->getModule($config, 'app.css'),
        ];

        $this->js = [
            $this->getModule($config, 'chunk-vendors.js'),
            $this->getModule($config, 'app.js'),
        ];

        parent::init();
    }

    /**
     * Return the URI to a module
     *
     * @param array $config
     * @param string $moduleName
     * @param string $type
     * @param bool $soft
     *
     * @return null|string
     * @throws NotFoundHttpException
     * @throws \yii\base\Exception
     */
    private function getModule(array $config, string $moduleName, string $type = 'modern', bool $soft = true): ?string
    {
        // Get the module entry
        $module = $this->getModuleEntry($config, $moduleName, $type, $soft);
        if ($module !== null) {
            $prefix = $this->isHot
                ? $config['devServer']['publicPath']
                : $config['server']['publicPath'];
            // If the module isn't a full URL, prefix it
            if (!UrlHelper::isAbsoluteUrl($module)) {
                $module = $this->combinePaths($prefix, $module);
                $module = strpos($module, '/') === 0 ? substr($module, 1) : $module;
            }
        }

        return $module;
    }

    /**
     * Return a module's raw entry from the manifest
     *
     * @param array $config
     * @param string $moduleName
     * @param string $type
     * @param bool $soft
     *
     * @return null|string
     * @throws NotFoundHttpException
     * @throws \yii\base\Exception
     */
    private function getModuleEntry(array $config, string $moduleName, string $type = 'modern', bool $soft = false): ?string
    {
        $module = null;
        // Get the manifest file
        $manifest = $this->getManifestFile($config, $type);
        if ($manifest !== null) {
            // Make sure it exists in the manifest
            if (empty($manifest[$moduleName])) {
                $this->reportError(Craft::t(
                    'app',
                    'Module does not exist in the manifest: {moduleName}',
                    ['moduleName' => $moduleName]
                ), $soft);

                return null;
            }
            $module = $manifest[$moduleName];
        }

        return $module;
    }

    /**
     * Return a JSON-decoded manifest file
     *
     * @param array $config
     * @param string $type
     *
     * @return array|null
     * @throws \yii\base\Exception
     */
    private function getManifestFile($config, $type = 'modern'): ?array
    {
        $pluginStoreService = Craft::$app->getPluginStore();
        $useDevServer = $pluginStoreService->useDevServer;

        $this->isHot = YII_DEBUG && $useDevServer;
        $manifest = null;

        while ($manifest === null) {
            $manifestPath = $this->isHot
                ? $config['devServer']['manifestPath']
                : $config['server']['manifestPath'];

            $path = $this->combinePaths($manifestPath, $config['manifest'][$type]);

            // Resolve any aliases
            $path = Craft::getAlias($path, false) ?: $path;

            // Make sure it's a full URL
            if (!UrlHelper::isAbsoluteUrl($path) && !is_file($path)) {
                try {
                    $path = UrlHelper::siteUrl($path);
                } catch (\Throwable $e) {
                    Craft::error($e->getMessage(), __METHOD__);
                }
            }

            $manifest = $this->getFileContents($path);

            // If the manifest isn't found, and it was hot, fall back on non-hot
            if ($manifest === null) {
                if ($this->isHot) {
                    // Try again, but not with home module replacement
                    $this->isHot = false;
                } else {
                    // Give up and return null
                    return null;
                }
            }
        }

        return $manifest;
    }

    /**
     * Return the contents of a file from the passed in path
     *
     * @param string $path
     * @return array|null
     */
    private function getFileContents(string $path): ?array
    {
        // Return the memoized manifest if it exists
        if (!array_key_exists($path, $this->files)) {
            // Create the dependency tags
            $dependency = new TagDependency([
                'tags' => [
                    self::CACHE_TAG,
                    self::CACHE_TAG . $path,
                ],
            ]);

            // Set the cache duration based on devMode
            $cacheDuration = YII_DEBUG ? self::DEVMODE_CACHE_DURATION : null;

            // Get the result from the cache, or parse the file
            $this->files[$path] = Craft::$app->getCache()->getOrSet(
                self::CACHE_KEY . $path,
                function() use ($path): ?array {
                    if (UrlHelper::isAbsoluteUrl($path)) {
                        /**
                         * Silly work-around for what appears to be a file_get_contents bug with https
                         * http://stackoverflow.com/questions/10524748/why-im-getting-500-error-when-using-file-get-contents-but-works-in-a-browser
                         */
                        $opts = [
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                            ],
                            'http' => [
                                'ignore_errors' => true,
                                'header' => "User-Agent:Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13\r\n",
                            ],
                        ];
                        $context = stream_context_create($opts);
                        $contents = @file_get_contents($path, false, $context);
                    } else {
                        $contents = @file_get_contents($path);
                    }

                    if (!$contents) {
                        return null;
                    }

                    return Json::decode($contents);
                },
                $cacheDuration,
                $dependency
            );
        }

        return $this->files[$path];
    }

    /**
     * Combined the passed in paths, whether file system or URL
     *
     * @param string ...$paths
     *
     * @return string
     */
    private static function combinePaths(string ...$paths): string
    {
        $last_key = \count($paths) - 1;
        array_walk($paths, function(&$val, $key) use ($last_key) {
            switch ($key) {
                case 0:
                    $val = rtrim($val, '/ ');
                    break;
                case $last_key:
                    $val = ltrim($val, '/ ');
                    break;
                default:
                    $val = trim($val, '/ ');
                    break;
            }
        });

        $first = array_shift($paths);
        $last = array_pop($paths);
        $paths = array_filter($paths);
        array_unshift($paths, $first);
        $paths[] = $last;

        return implode('/', $paths);
    }

    /**
     * @param string $error
     * @param bool $soft
     * @throws NotFoundHttpException
     */
    private function reportError(string $error, bool $soft = false): void
    {
        if (YII_DEBUG && !$soft) {
            throw new NotFoundHttpException($error);
        }
        Craft::error($error, __METHOD__);
    }
}
