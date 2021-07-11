<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\helpers\App;
use craft\helpers\FileHelper;
use craft\web\AssetBundle;
use Dotenv\Dotenv;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class HotReloadAssetBundle
 *
 * @package craft\base
 */
class HotReloadAssetBundle extends AssetBundle
{
    /**
     * Filename for the environment file.
     */
    const ENV_FILENAME = '.env';

    /**
     * Asset directory.
     *
     * @var string|null
     */
    private $_assetDir;

    /**
     * Webpack dev server public path/host.
     *
     * @var string|null
     */
    private $_devServerPublic;

    /**
     * Loaded status of the environment file.
     *
     * @var bool
     */
    private $_envLoaded = false;

    /**
     * Running status of the webpack dev server.
     *
     * @var bool|null
     */
    private $_isDevServerRunning;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $reflector = new \ReflectionClass(self::class);
        $this->_assetDir = dirname($reflector->getFileName());

        $this->_loadEnvFile();

        $this->_updateCss();
        $this->_updateJs();
    }

    /**
     * Load the environment variables.
     *
     * @return void
     */
    private function _loadEnvFile(): void
    {
        if ($this->_envLoaded) {
            return;
        }

        $envFilePath = $this->_getEnvFile();

        if (!$envFilePath) {
            return;
        }

        $dotEnv = Dotenv::create($envFilePath, self::ENV_FILENAME);
        $dotEnv->load();

        $this->_envLoaded = true;
    }

    /**
     * Update the JS file paths.
     *
     * @return void
     */
    private function _updateJs(): void
    {
        if (empty($this->js)) {
            return;
        }

        $this->js = array_map([$this, '_prependDevServer'], $this->js);
    }

    /**
     * Update the CSS file paths.
     *
     * @return void
     */
    private function _updateCss(): void
    {
        if (empty($this->css)) {
            return;
        }

        $this->css = array_map([$this, '_prependDevServer'], $this->css);
    }

    /**
     * Prefix the string with the dev server host if the dev server is running.
     *
     * @param string $val
     * @return string
     */
    private function _prependDevServer(string $val): string
    {
        $devServer = rtrim($this->_getDevServer(), '/');
        return ($devServer ? $devServer . '/' : '') . $val;
    }

    /**
     * Get the dev server public path.
     *
     * @return string
     */
    private function _getDevServer(): string
    {
        if ($this->_devServerPublic !== null) {
            return $this->_devServerPublic;
        }

        if ((!$devServerPublicLoopback = App::env('DEV_SERVER_PUBLIC_LOOPBACK')) || (!$this->_devServerPublic = App::env('DEV_SERVER_PUBLIC'))) {
            $this->_devServerPublic = '';
        }

        if ($devServerPublicLoopback && !$this->_isDevServerRunning($devServerPublicLoopback)) {
            $this->_devServerPublic = '';
        }

        return $this->_devServerPublic;
    }

    /**
     * Returns the running status of the webpack dev server.
     *
     * @param string $devServerPublic
     * @return bool
     */
    private function _isDevServerRunning(string $devServerPublic): bool
    {
        if ($this->_isDevServerRunning !== null) {
            return $this->_isDevServerRunning;
        }

        $client = Craft::createGuzzleClient();
        try {
            $client->head($devServerPublic);
            $this->_isDevServerRunning = true;
        } catch (GuzzleException $e) {
            $this->_isDevServerRunning = false;
        }

        return $this->_isDevServerRunning;
    }

    /**
     * Returns the environment file to be loaded.
     *
     * @return false|string
     */
    private function _getEnvFile()
    {
        $localPath = FileHelper::normalizePath($this->_assetDir);
        $rootPath = FileHelper::normalizePath(Craft::getAlias('@craft') . DIRECTORY_SEPARATOR . '..');

        if (file_exists($localPath . DIRECTORY_SEPARATOR . self::ENV_FILENAME)) {
            return $localPath;
        }

        if (file_exists($rootPath . DIRECTORY_SEPARATOR . self::ENV_FILENAME)) {
            return $rootPath;
        }

        return false;
    }
}