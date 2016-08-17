<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\cache\AppPathDependency;
use craft\app\dates\DateTime;
use craft\app\helpers\Io;
use craft\app\helpers\Path as PathHelper;
use craft\app\helpers\StringHelper;
use craft\app\helpers\Url;
use Exception;
use yii\base\Component;
use yii\helpers\FileHelper;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class Resources service.
 *
 * An instance of the Resources service is globally accessible in Craft via [[Application::resources `Craft::$app->getResources()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Resources extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var
     */
    public $dateParam;

    // Public Methods
    // =========================================================================

    /**
     * Returns the cached file system path for a given resource, if we have it.
     *
     * @param string $path
     *
     * @return string|null
     */
    public function getCachedResourcePath($path)
    {
        $realPath = Craft::$app->getCache()->get('resourcePath:'.$path);

        if ($realPath && Io::fileExists($realPath)) {
            return $realPath;
        }

        return null;
    }

    /**
     * Caches a file system path for a given resource.
     *
     * @param string $path
     * @param string $realPath
     *
     * @return void
     */
    public function cacheResourcePath($path, $realPath)
    {
        if (!$realPath) {
            $realPath = ':(';
        }

        Craft::$app->getCache()->set('resourcePath:'.$path, $realPath, null, new AppPathDependency());
    }

    /**
     * Resolves a resource path to the actual file system path, or returns false if the resource cannot be found.
     *
     * @param string $path
     *
     * @return string
     * @throws NotFoundHttpException if the requested image transform cannot be found
     * @throws ServerErrorHttpException if reasons
     */
    public function getResourcePath($path)
    {
        $segs = explode('/', $path);

        // Special resource routing
        if (isset($segs[0])) {
            switch ($segs[0]) {
                case 'defaultuserphoto': {
                    return Craft::$app->getPath()->getResourcesPath().'/images/user.svg';
                }

                case 'tempuploads': {
                    array_shift($segs);

                    return Craft::$app->getPath()->getTempUploadsPath().'/'.implode('/', $segs);
                }

                case 'tempassets': {
                    array_shift($segs);

                    return Craft::$app->getPath()->getAssetsTempVolumePath().'/'.implode('/', $segs);
                }

                case 'resized': {
                    if (empty($segs[1]) || empty($segs[2]) || !is_numeric($segs[1]) || !is_numeric($segs[2])) {
                        return $this->_getBrokenImageThumbPath();
                    }

                    $fileModel = Craft::$app->getAssets()->getAssetById($segs[1]);

                    if (empty($fileModel)) {
                        return $this->_getBrokenImageThumbPath();
                    }

                    $size = $segs[2];

                    // Make sure plugins are loaded in case the asset lives in a plugin-supplied volume type
                    Craft::$app->getPlugins()->loadPlugins();

                    try {
                        return Craft::$app->getAssetTransforms()->getResizedAssetServerPath($fileModel, $size);
                    } catch (\Exception $e) {
                        return $this->_getBrokenImageThumbPath();
                    }
                }

                case 'icons': {
                    if (empty($segs[1]) || !preg_match('/^\w+/i', $segs[1])) {
                        return false;
                    }

                    return $this->_getIconPath($segs[1]);
                }

                case 'rebrand': {
                    if (!in_array($segs[1], ['logo', 'icon'])) {
                        return false;
                    }

                    return Craft::$app->getPath()->getRebrandPath().'/'.$segs[1]."/".$segs[2];
                }

                case 'transforms': {
                    // Make sure plugins are loaded in case the asset lives in a plugin-supplied volume type
                    Craft::$app->getPlugins()->loadPlugins();

                    try {
                        if (!empty($segs[1])) {
                            $transformIndexModel = Craft::$app->getAssetTransforms()->getTransformIndexModelById((int)$segs[1]);
                        }

                        if (empty($transformIndexModel)) {
                            throw new NotFoundHttpException(Craft::t('app', 'Image transform not found'));
                        }

                        $url = Craft::$app->getAssetTransforms()->ensureTransformUrlByIndexModel($transformIndexModel);
                    } catch (Exception $exception) {
                        throw new ServerErrorHttpException($exception->getMessage());
                    }

                    Craft::$app->getResponse()->redirect($url);
                    Craft::$app->end();
                    break;
                }

                case '404': {
                    throw new NotFoundHttpException(Craft::t('app', 'Resource not found'));
                }
            }
        }

        // Check app/resources folder first.
        $appResourcePath = Craft::$app->getPath()->getResourcesPath().'/'.$path;

        if (Io::fileExists($appResourcePath)) {
            return $appResourcePath;
        }

        // See if the first segment is a plugin handle.
        if (isset($segs[0])) {
            $pluginResourcePath = Craft::$app->getPath()->getPluginsPath().'/'.$segs[0].'/'.'resources/'.implode('/',
                    array_splice($segs, 1));

            if (Io::fileExists($pluginResourcePath)) {
                return $pluginResourcePath;
            }
        }

        // Maybe a plugin wants to do something custom with this URL
        $pluginPath = Craft::$app->getPlugins()->callFirst('getResourcePath',
            [$path], true);

        if ($pluginPath && Io::fileExists($pluginPath)) {
            return $pluginPath;
        }

        // Couldn't find the file
        return false;
    }

    /**
     * Sends a resource back to the browser.
     *
     * @param string $path
     *
     * @return void
     * @throws ForbiddenHttpException if the requested resource path is not contained within the allowed directories
     * @throws NotFoundHttpException if the requested resource cannot be found
     */
    public function sendResource($path)
    {
        if (PathHelper::ensurePathIsContained($path) === false) {
            throw new ForbiddenHttpException(Craft::t('app', 'Resource path not contained within allowed directories'));
        }

        $cachedPath = $this->getCachedResourcePath($path);

        if ($cachedPath) {
            if ($cachedPath == ':(') {
                // 404
                $realPath = false;
            } else {
                // We've got it already
                $realPath = $cachedPath;
            }
        } else {
            // We don't have a cache of the file system path, so let's get it
            $realPath = $this->getResourcePath($path);

            // Now cache it
            $this->cacheResourcePath($path, $realPath);
        }

        if ($realPath === false || !Io::fileExists($realPath)) {
            throw new NotFoundHttpException(Craft::t('app', 'Resource not found'));
        }

        // If there is a timestamp and HTTP_IF_MODIFIED_SINCE exists, check the timestamp against requested file's last
        // modified date. If the last modified date is less than the timestamp, return a 304 not modified and let the
        // browser serve it from cache.
        $timestamp = Craft::$app->getRequest()->getParam($this->dateParam, null);

        if ($timestamp !== null && array_key_exists('HTTP_IF_MODIFIED_SINCE',
                $_SERVER)
        ) {
            $requestDate = DateTime::createFromFormat('U', $timestamp);
            $lastModifiedFileDate = Io::getLastTimeModified($realPath);

            if ($lastModifiedFileDate && $lastModifiedFileDate <= $requestDate) {
                // Let the browser serve it from cache.
                Craft::$app->getResponse()->setStatusCode(304);
                Craft::$app->end();
            }
        }

        $filename = Io::getFilename($realPath);
        $mimeType = FileHelper::getMimeTypeByExtension($realPath);
        $response = Craft::$app->getResponse();

        $options = [
            'mimeType' => $mimeType,
            'inline' => true,
        ];

        if (Craft::$app->getRequest()->getQueryParam($this->dateParam)) {
            $response->setCacheHeaders();
            $response->setLastModifiedHeader($realPath);
        }

        // Is this a CSS file?
        if ($mimeType == 'text/css') {
            // Normalize the URLs
            $contents = Io::getFileContents($realPath);
            $contents = preg_replace_callback('/(url\(([\'"]?))(.+?)(\2\))/',
                [&$this, '_normalizeCssUrl'], $contents);

            $response->sendContentAsFile($contents, $filename, $options);
        } else {
            $response->sendFile($realPath, $filename, $options);
        }

        // You shall not pass.
        Craft::$app->end();
    }

    // Private Methods
    // =========================================================================

    /**
     * @param $match
     *
     * @return string
     */
    private function _normalizeCssUrl($match)
    {
        // Ignore root-relative, absolute, and data: URLs
        if (preg_match('/^(\/|https?:\/\/|data:)/', $match[3])) {
            return $match[0];
        }

        // Clean up any relative folders at the beginning of the CSS URL
        $requestFolder = Io::getFolderName(Craft::$app->getRequest()->getPathInfo());
        $requestFolderParts = array_filter(explode('/', $requestFolder));
        $cssUrlParts = array_filter(explode('/', $match[3]));

        while (isset($cssUrlParts[0]) && $cssUrlParts[0] == '..' && $requestFolderParts) {
            array_pop($requestFolderParts);
            array_shift($cssUrlParts);
        }

        $pathParts = array_merge($requestFolderParts, $cssUrlParts);
        $path = implode('/', $pathParts);
        $url = Url::getUrl($path);

        // Is this going to be a resource URL?
        $rootResourceUrl = Url::getUrl(Craft::$app->getConfig()->getResourceTrigger()).'/';
        $rootResourceUrlLength = strlen($rootResourceUrl);

        if (strncmp($rootResourceUrl, $url, $rootResourceUrlLength) === 0) {
            // Isolate the relative resource path
            $resourcePath = substr($url, $rootResourceUrlLength);

            // Give Url a chance to add the timestamp
            $url = Url::getResourceUrl($resourcePath);
        }

        // Return the normalized CSS URL declaration
        return $match[1].$url.$match[4];
    }

    /**
     * Returns the icon path for a given extension
     *
     * @param $ext
     *
     * @return string
     */
    private function _getIconPath($ext)
    {
        $pathService = Craft::$app->getPath();
        $sourceIconPath = $pathService->getResourcesPath().'/images/file.svg';
        $extLength = mb_strlen($ext);

        if ($extLength > 5) {
            // Too long; just use the blank file icon
            return $sourceIconPath;
        }

        // See if the icon already exists
        $iconPath = $pathService->getAssetsIconsPath().'/'.StringHelper::toLowerCase($ext).'.svg';

        if (Io::fileExists($iconPath)) {
            return $iconPath;
        }

        // Create a new one
        $svgContents = Io::getFileContents($sourceIconPath);
        $textSize = ($extLength <= 3 ? '26' : ($extLength == 4 ? '22' : '18'));
        $textNode = '<text x="50" y="73" text-anchor="middle" font-family="sans-serif" fill="#8F98A3" '.
            'font-size="'.$textSize.'">'.
            StringHelper::toUpperCase($ext).
            '</text>';
        $svgContents = str_replace('<!-- EXT -->', $textNode, $svgContents);
        Io::writeToFile($iconPath, $svgContents);

        return $iconPath;
    }

    /**
     * Returns the path to the broken image thumbnail.
     *
     * @return string
     */
    private function _getBrokenImageThumbPath()
    {
        //http_response_code(404);
        return Craft::$app->getPath()->getResourcesPath().'/images/brokenimage.svg';
    }
}
