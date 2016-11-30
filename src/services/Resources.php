<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\cache\AppPathDependency;
use craft\dates\DateTime;
use craft\events\ResolveResourcePathEvent;
use craft\helpers\Io;
use craft\helpers\Path as PathHelper;
use craft\helpers\StringHelper;
use craft\helpers\Url;
use Exception;
use yii\base\Component;
use craft\helpers\FileHelper;
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
    /**
     * @event ResolveResourcePathEvent The event that is triggered when mapping a resource URI to a file path.
     */
    const EVENT_RESOLVE_RESOURCE_PATH = 'resolveResourcePath';

    // Properties
    // =========================================================================

    /**
     * @var
     */
    public $dateParam;

    // Public Methods
    // =========================================================================

    /**
     * Returns the cached file system path for a given resource URI, if we have it.
     *
     * @param string $uri
     *
     * @return string|null
     */
    public function getCachedResourcePath($uri)
    {
        $path = Craft::$app->getCache()->get('resourcePath:'.$uri);

        if ($path && Io::fileExists($path)) {
            return $path;
        }

        return null;
    }

    /**
     * Caches a file system path for a given resource.
     *
     * @param string $uri
     * @param string $path
     *
     * @return void
     */
    public function cacheResourcePath($uri, $path)
    {
        if (!$path) {
            $path = ':(';
        }

        Craft::$app->getCache()->set('resourcePath:'.$uri, $path, null, new AppPathDependency());
    }

    /**
     * Resolves a resource URI to the actual file system path, or returns false if the resource cannot be found.
     *
     * @param string $uri
     *
     * @return string
     * @throws NotFoundHttpException if the requested image transform cannot be found
     * @throws ServerErrorHttpException if reasons
     */
    public function resolveResourcePath($uri)
    {
        $segs = explode('/', $uri);

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
        $appResourcePath = Craft::$app->getPath()->getResourcesPath().'/'.$uri;

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
        Craft::$app->getPlugins()->loadPlugins();
        $event = new ResolveResourcePathEvent([
            'uri' => $uri
        ]);
        $this->trigger(self::EVENT_RESOLVE_RESOURCE_PATH, $event);

        if ($event->path !== null && Io::fileExists($event->path)) {
            return $event->path;
        }

        // Couldn't find the file
        return false;
    }

    /**
     * Sends a resource back to the browser.
     *
     * @param string $uri
     *
     * @return void
     * @throws ForbiddenHttpException if the requested resource URI is not contained within the allowed directories
     * @throws NotFoundHttpException if the requested resource cannot be found
     */
    public function sendResource($uri)
    {
        if (PathHelper::ensurePathIsContained($uri) === false) {
            throw new ForbiddenHttpException(Craft::t('app', 'Resource path not contained within allowed directories'));
        }

        $cachedPath = $this->getCachedResourcePath($uri);

        if ($cachedPath) {
            if ($cachedPath == ':(') {
                // 404
                $path = false;
            } else {
                // We've got it already
                $path = $cachedPath;
            }
        } else {
            // We don't have a cache of the file system path, so let's get it
            $path = $this->resolveResourcePath($uri);

            // Now cache it
            $this->cacheResourcePath($uri, $path);
        }

        if ($path === false || !Io::fileExists($path)) {
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
            $lastModifiedFileDate = Io::getLastTimeModified($path);

            if ($lastModifiedFileDate && $lastModifiedFileDate <= $requestDate) {
                // Let the browser serve it from cache.
                Craft::$app->getResponse()->setStatusCode(304);
                Craft::$app->end();
            }
        }

        $filename = Io::getFilename($path);
        $mimeType = FileHelper::getMimeTypeByExtension($path);
        $response = Craft::$app->getResponse();

        $options = [
            'mimeType' => $mimeType,
            'inline' => true,
        ];

        if (Craft::$app->getRequest()->getQueryParam($this->dateParam)) {
            $response->setCacheHeaders();
            $response->setLastModifiedHeader($path);
        }

        // Is this a CSS file?
        if ($mimeType == 'text/css') {
            // Normalize the URLs
            $contents = Io::getFileContents($path);
            $contents = preg_replace_callback('/(url\(([\'"]?))(.+?)(\2\))/',
                [&$this, '_normalizeCssUrl'], $contents);

            $response->sendContentAsFile($contents, $filename, $options);
        } else {
            $response->sendFile($path, $filename, $options);
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
        $url = Url::url($path);

        // Is this going to be a resource URL?
        $rootResourceUrl = Url::url(Craft::$app->getConfig()->getResourceTrigger()).'/';
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
