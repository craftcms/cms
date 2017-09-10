<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\cache\AppPathDependency;
use craft\events\ResolveResourcePathEvent;
use craft\helpers\FileHelper;
use craft\helpers\Path as PathHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use yii\base\Component;
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
    public function getCachedResourcePath(string $uri)
    {
        $path = Craft::$app->getCache()->get('resourcePath:'.$uri);

        if ($path === false || !file_exists($path)) {
            return null;
        }

        return $path;
    }

    /**
     * Caches a file system path for a given resource.
     *
     * @param string       $uri
     * @param string|false $path
     *
     * @return void
     */
    public function cacheResourcePath(string $uri, $path)
    {
        if ($path === false) {
            $path = ':(';
        }

        Craft::$app->getCache()->set('resourcePath:'.$uri, $path, null, new AppPathDependency());
    }

    /**
     * Resolves a resource URI to the actual file system path, or returns false if the resource cannot be found.
     *
     * @param string $uri
     *
     * @return string|false
     * @throws NotFoundHttpException if the requested image transform cannot be found
     * @throws ServerErrorHttpException if reasons
     */
    public function resolveResourcePath(string $uri)
    {
        $segs = explode('/', $uri);

        // Special resource routing
        if (isset($segs[0])) {
            switch ($segs[0]) {
                case 'tempuploads':
                    array_shift($segs);

                    return Craft::$app->getPath()->getTempUploadsPath().DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $segs);
                case 'tempassets':
                    array_shift($segs);

                    return Craft::$app->getPath()->getAssetsTempVolumePath().DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $segs);
                case 'resized':
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
                        $path = Craft::$app->getAssetTransforms()->getResizedAssetServerPath($fileModel, $size);
                    } catch (\Throwable $e) {
                        $path = $this->_getBrokenImageThumbPath();
                    }

                    return $path;
                case 'icons':
                    if (empty($segs[1]) || !preg_match('/^\w+/i', $segs[1])) {
                        return false;
                    }

                    return $this->_getIconPath($segs[1]);
                case 'rebrand':
                    if (!in_array($segs[1], ['logo', 'icon'], true)) {
                        return false;
                    }

                    return Craft::$app->getPath()->getRebrandPath().DIRECTORY_SEPARATOR.$segs[1].DIRECTORY_SEPARATOR.$segs[2];
                case 'transforms':
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
                    } catch (\Throwable $exception) {
                        throw new ServerErrorHttpException($exception->getMessage());
                    }
                    Craft::$app->getResponse()->redirect($url);
                    Craft::$app->end();
                    break;
                case '404':
                    throw new NotFoundHttpException(Craft::t('app', 'Resource not found'));
            }
        }

        // Maybe a plugin wants to do something custom with this URL
        Craft::$app->getPlugins()->loadPlugins();
        $event = new ResolveResourcePathEvent([
            'uri' => $uri
        ]);
        $this->trigger(self::EVENT_RESOLVE_RESOURCE_PATH, $event);

        if ($event->path !== null && is_file($event->path)) {
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
    public function sendResource(string $uri)
    {
        if (PathHelper::ensurePathIsContained($uri) === false) {
            throw new ForbiddenHttpException(Craft::t('app', 'Resource path not contained within allowed directories'));
        }

        $cachedPath = $this->getCachedResourcePath($uri);

        if ($cachedPath !== null) {
            if ($cachedPath === ':(') {
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

        if ($path === false || !is_file($path)) {
            throw new NotFoundHttpException(Craft::t('app', 'Resource not found'));
        }

        // If there is a timestamp and HTTP_IF_MODIFIED_SINCE exists, check the timestamp against requested file's last
        // modified date. If the last modified date is less than the timestamp, return a 304 not modified and let the
        // browser serve it from cache.
        $timestamp = Craft::$app->getRequest()->getParam($this->dateParam, null);

        if ($timestamp !== null && array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER)) {
            $lastModifiedFileDate = filemtime($path);

            if ($lastModifiedFileDate && $lastModifiedFileDate <= $timestamp) {
                // Let the browser serve it from cache.
                Craft::$app->getResponse()->setStatusCode(304);
                Craft::$app->end();
            }
        }

        $filename = pathinfo($path, PATHINFO_BASENAME);
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
        if ($mimeType === 'text/css') {
            // Normalize the URLs
            $contents = file_get_contents($path);
            $contents = preg_replace_callback('/(url\(([\'"]?))(.+?)(\2\))/', [&$this, '_normalizeCssUrl'], $contents);

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
     * @param array $match
     *
     * @return string
     */
    private function _normalizeCssUrl(array $match): string
    {
        // Ignore root-relative, absolute, and data: URLs
        if (preg_match('/^(\/|https?:\/\/|data:)/', $match[3])) {
            return $match[0];
        }

        // Clean up any relative folders at the beginning of the CSS URL
        $requestFolder = pathinfo(Craft::$app->getRequest()->getPathInfo(), PATHINFO_DIRNAME);
        $requestFolderParts = array_filter(explode('/', $requestFolder));
        $cssUrlParts = array_filter(explode('/', $match[3]));

        while (isset($cssUrlParts[0]) && $cssUrlParts[0] === '..' && !empty($requestFolderParts)) {
            array_pop($requestFolderParts);
            array_shift($cssUrlParts);
        }

        $pathParts = array_merge($requestFolderParts, $cssUrlParts);
        $path = implode('/', $pathParts);
        $url = UrlHelper::url($path);

        // Is this going to be a resource URL?
        $rootResourceUrl = UrlHelper::url(UrlHelper::resourceTrigger()).'/';

        if (strpos($url, $rootResourceUrl) === 0) {
            // Isolate the relative resource path
            $resourcePath = substr($url, strlen($rootResourceUrl));

            // Give Url a chance to add the timestamp
            $url = UrlHelper::resourceUrl($resourcePath);
        }

        // Return the normalized CSS URL declaration
        return $match[1].$url.$match[4];
    }

    /**
     * Returns the icon path for a given extension
     *
     * @param string $ext
     *
     * @return string
     */
    private function _getIconPath(string $ext): string
    {
        $pathService = Craft::$app->getPath();
        $sourceIconPath = Craft::getAlias('@app/icons/file.svg');
        $extLength = mb_strlen($ext);

        if ($extLength > 5) {
            // Too long; just use the blank file icon
            return $sourceIconPath;
        }

        // See if the icon already exists
        $iconPath = $pathService->getAssetsIconsPath().DIRECTORY_SEPARATOR.StringHelper::toLowerCase($ext).'.svg';

        if (file_exists($iconPath)) {
            return $iconPath;
        }

        // Create a new one
        $svgContents = file_get_contents($sourceIconPath);
        if ($extLength <= 3) {
            $textSize = '26';
        } else {
            $textSize = $extLength === 4 ? '22' : '18';
        }
        $textNode = '<text x="50" y="73" text-anchor="middle" font-family="sans-serif" fill="#8F98A3" '.
            'font-size="'.$textSize.'">'.
            StringHelper::toUpperCase($ext).
            '</text>';
        $svgContents = str_replace('<!-- EXT -->', $textNode, $svgContents);
        FileHelper::writeToFile($iconPath, $svgContents);

        return $iconPath;
    }

    /**
     * Returns the path to the broken image thumbnail.
     *
     * @return string
     */
    private function _getBrokenImageThumbPath(): string
    {
        //http_response_code(404);
        return Craft::getAlias('@app/icons/broken-image.svg');
    }
}
