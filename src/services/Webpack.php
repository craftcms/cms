<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use GuzzleHttp\Exception\GuzzleException;
use yii\base\Component;

/**
 * Webpack service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getWebpack()|`Craft::$app->webpack()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.22
 */
class Webpack extends Component
{
    /**
     * @var array Dev servers public addresses
     */
    private $_devServers = [];

    /**
     * @var array Dev servers running statuses
     */
    private $_isDevServerRunning = [];

    /**
     * @var array
     */
    private $_envFileVariables = [];

    /**
     * @var array
     */
    private $_serverResponse = [];

    /**
     * @var boolean[]
     */
    private $_checkedEnvDirs = [];

    /**
     * Returns the environment file.
     *
     * @param string $class
     * @return string|null
     * @throws \ReflectionException
     */
    private function _getEnvFilePath(string $class): ?string
    {
        $assetDir = $this->_getDirectory($class);

        // Search up the directory tree for the .env file in $assetPath
        while ($assetDir) {
            $assetDir = FileHelper::normalizePath($assetDir);
            $assetPath = $assetDir . DIRECTORY_SEPARATOR . '.env';

            if (!isset($this->_checkedEnvDirs[$assetDir])) {
                // Make sure it's within the allowed base paths
                if (!App::isPathAllowed($assetDir)) {
                    $this->_checkedEnvDirs[$assetDir] = false;
                    break;
                }

                $this->_checkedEnvDirs[$assetDir] = file_exists($assetPath);
            }

            if ($this->_checkedEnvDirs[$assetDir]) {
                return $assetPath;
            }

            if ($assetDir === DIRECTORY_SEPARATOR || $assetDir === dirname($assetDir)) {
                break;
            }

            $assetDir = dirname($assetDir);
        }

        return null;
    }

    /**
     * @param string $class
     * @return string
     * @throws \ReflectionException
     */
    private function _getDirectory(string $class): string
    {
        $reflector = new \ReflectionClass($class);
        $dir = dirname($reflector->getFileName());

        return FileHelper::normalizePath($dir);
    }

    /**
     * Load the environment variables.
     *
     * @param string $class
     * @return array|null
     * @throws \ReflectionException
     */
    private function _getEnvVars(string $class): ?array
    {
        $settings = $this->_envFileVariables[$class] ?? null;

        if ($settings !== null) {
            return $settings;
        }

        $envFile = $this->_getEnvFilePath($class);

        // TODO: Use DotEnv::parse() when we version is bumped.
        $fileContents = file_exists($envFile) ? @file_get_contents($envFile) : null;

        if (!$fileContents) {
            return $this->_envFileVariables[$class] = [];
        }

        $pattern = '/^([a-zA-Z_]+)=(")?(.*?)(?(2)\2|)$/m';
        preg_match_all($pattern, $fileContents, $matches, PREG_SET_ORDER, 0);

        $this->_envFileVariables[$class] = [];

        foreach ($matches as $match) {
            $this->_envFileVariables[$class][$match[1]] = $match[3];
        }

        return $this->_envFileVariables[$class];
    }

    /**
     * @param string $class
     * @return string|null
     * @throws \Exception
     */
    private function _getDevServerLoopback(string $class): ?string
    {
        return ArrayHelper::getValue($this->_getEnvVars($class), 'DEV_SERVER_LOOPBACK');
    }

    /**
     * @param string $class
     * @return string|null
     * @throws \Exception
     */
    private function _getDevServerPublic(string $class): ?string
    {
        return ArrayHelper::getValue($this->_getEnvVars($class), 'DEV_SERVER_PUBLIC');
    }

    /**
     * Get the dev server public path.
     *
     * @param string $class
     * @return string
     * @throws \Exception
     */
    public function getDevServer(string $class): string
    {
        $devServer = $this->_devServers[$class] ?? null;

        if ($devServer !== null) {
            return $devServer;
        }

        if ((!$devServerPublicLoopback = $this->_getDevServerLoopback($class)) || (!$this->_devServers[$class] = $this->_getDevServerPublic($class))) {
            $this->_devServers[$class] = '';
        }

        if ($devServerPublicLoopback && !$this->_isDevServerRunning($class, $devServerPublicLoopback)) {
            $this->_devServers[$class] = '';
        }

        return $this->_devServers[$class];
    }

    /**
     * Returns the running status of the webpack dev server.
     *
     * @param string $class
     * @param string $loopback
     * @return bool
     * @throws GuzzleException
     * @throws \ReflectionException
     */
    private function _isDevServerRunning(string $class, string $loopback): bool
    {
        $isDevServerRunning = $this->_isDevServerRunning[$class] ?? null;
        if ($isDevServerRunning !== null) {
            return $isDevServerRunning;
        }

        if (isset($this->_serverResponse[$loopback])) {
            return $this->_isDevServerRunning[$class] = $this->_matchAsset($this->_serverResponse[$loopback], $class);
        }

        $client = Craft::createGuzzleClient();
        try {
            $res = $client->get(StringHelper::ensureRight($loopback, '/') . 'which-asset');
            if ($res->getStatusCode() !== 200) {
                throw new \Exception('Could not connect to dev server.');
            }

            if (!$body = $res->getBody()) {
                throw new \Exception('Response has no body.');
            }

            $contents = $body->getContents();
            $json = json_decode($contents, true);

            $this->_serverResponse[$loopback] = $json;
            $this->_isDevServerRunning[$class] = $this->_matchAsset($this->_serverResponse[$loopback], $class);
        } catch (\Exception $e) {
            return $this->_isDevServerRunning[$class] = false;
        }

        return $this->_isDevServerRunning[$class];
    }

    /**
     * @param array $json
     * @param string $class
     * @return bool
     */
    private function _matchAsset(array $json, string $class): bool
    {
        if (empty($json) || !array_key_exists('classes', $json) || !is_array($json['classes']) || empty($json['classes'])) {
            return false;
        }

        return in_array($class, $json['classes']);
    }
}
