<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use DateTime;
use Throwable;
use yii\base\Component;
use yii\base\Exception;
use yii\caching\TagDependency;

/**
 * Template Caches service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getTemplateCaches()|`Craft::$app->templateCaches`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class TemplateCaches extends Component
{
    /**
     * @var bool Whether template caching should be enabled for this request
     * @see _isTemplateCachingEnabled()
     */
    private bool $_enabled;

    /**
     * @var bool Whether global template caches should be enabled for this request
     * @see _isTemplateCachingEnabled()
     */
    private bool $_enabledGlobally;

    /**
     * @var string|null The current request's path
     * @see _path()
     */
    private ?string $_path = null;

    /**
     * Returns a cached template by its key.
     *
     * @param string $key The template cache key
     * @param bool $global Whether the cache would have been stored globally.
     * @param bool $registerResources Whether JS and CSS resources captured by the cache should be registered
     * @return string|null
     * @throws Exception if this is a console request and `false` is passed to `$global`
     */
    public function getTemplateCache(string $key, bool $global, bool $registerResources = false): ?string
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled($global) === false) {
            return null;
        }

        $cacheKey = $this->_cacheKey($key, $global);
        $data = Craft::$app->getCache()->get($cacheKey);

        if ($data === false) {
            return null;
        }

        [$body, $cacheInfo, $bufferedJs, $bufferedScripts, $bufferedCss, $bufferedJsFiles, $bufferedCssFiles, $bufferedHtml] = array_pad($data, 8, null);

        // If we're actively collecting element cache info, register this cache's tags and duration
        $elementsService = Craft::$app->getElements();
        if ($elementsService->getIsCollectingCacheInfo()) {
            if (isset($cacheInfo['tags'])) {
                $elementsService->collectCacheTags($cacheInfo['tags']);
                $elementsService->setCacheExpiryDate(DateTimeHelper::toDateTime($cacheInfo['expiryDate']));
            } else {
                $elementsService->collectCacheTags($cacheInfo);
            }
        }

        // Register JS and CSS tags
        if ($registerResources) {
            $this->_registerResources(
                $bufferedJs ?? [],
                $bufferedScripts ?? [],
                $bufferedCss ?? [],
                $bufferedJsFiles ?? [],
                $bufferedCssFiles ?? [],
                $bufferedHtml ?? []
            );
        }

        return $body;
    }

    /**
     * Starts a new template cache.
     *
     * @param bool $withResources Whether JS and CSS code registered with [[\craft\web\View::registerJs()]],
     * [[\craft\web\View::registerScript()]], [[\craft\web\View::registerCss()]],
     * [[\craft\web\View::registerJsFile()]], [[\craft\web\View::registerCssFile()]], and [[\craft\web\View::registerHtml()]]
     * should be captured and included in the cache. If this is `true`, be sure to pass `$withResources = true`
     * to [[endTemplateCache()]] as well.
     * @param bool $global Whether the cache should be stored globally.
     */
    public function startTemplateCache(bool $withResources = false, bool $global = false): void
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled($global) === false) {
            return;
        }

        Craft::$app->getElements()->startCollectingCacheInfo();

        if ($withResources) {
            $view = Craft::$app->getView();
            $view->startJsBuffer();
            $view->startScriptBuffer();
            $view->startCssBuffer();
            $view->startJsFileBuffer();
            $view->startCssFileBuffer();
            $view->startHtmlBuffer();
        }
    }

    /**
     * Ends a template cache.
     *
     * @param string $key The template cache key.
     * @param bool $global Whether the cache should be stored globally.
     * @param string|null $duration How long the cache should be stored for. Should be a [relative time format](https://php.net/manual/en/datetime.formats.relative.php).
     * @param mixed $expiration When the cache should expire.
     * @param string $body The contents of the cache.
     * @param bool $withResources Whether JS and CSS code registered with [[\craft\web\View::registerJs()]],
     * [[\craft\web\View::registerScript()]], [[\craft\web\View::registerCss()]],
     * [[\craft\web\View::registerJsFile()]], [[\craft\web\View::registerCssFile()]], and [[\craft\web\View::registerHtml()]]
     * should be captured and included in the cache.
     * @throws Exception if this is a console request and `false` is passed to `$global`
     * @throws Throwable
     */
    public function endTemplateCache(string $key, bool $global, ?string $duration, mixed $expiration, string $body, bool $withResources = false): void
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled($global) === false) {
            return;
        }

        [$dep, $maxDuration] = Craft::$app->getElements()->stopCollectingCacheInfo();

        if ($withResources) {
            $view = Craft::$app->getView();
            $bufferedJs = $view->clearJsBuffer(false, false);
            $bufferedScripts = $view->clearScriptBuffer();
            $bufferedCss = $view->clearCssBuffer();
            $bufferedJsFiles = $view->clearJsFileBuffer();
            $bufferedCssFiles = $view->clearCssFileBuffer();
            $bufferedHtml = $view->clearHtmlBuffer();
        }

        // If there are any transform generation URLs in the body, don't cache it.
        // stripslashes($body) in case the URL has been JS-encoded or something.
        $saveCache = !StringHelper::contains(stripslashes($body), 'assets/generate-transform');

        if ($saveCache) {
            if (!$dep) {
                $dep = new TagDependency();
            }

            // Always add a `template` tag
            $dep->tags[] = 'template';

            if ($maxDuration) {
                $expiryDate = DateTimeHelper::now()->modify("+$maxDuration seconds");
                $cacheInfo = [
                    'tags' => $dep->tags,
                    'expiryDate' => DateTimeHelper::toIso8601($expiryDate),
                ];
            } else {
                $cacheInfo = $dep->tags;
            }

            $cacheValue = [$body, $cacheInfo];
        }

        if ($withResources) {
            // Parse the JS/CSS code and tag attributes out of the <script> and <style> tags
            $bufferedScripts = array_map(fn(array $tags) => $this->_parseInlineResourceTags($tags), $bufferedScripts);
            $bufferedCss = $this->_parseInlineResourceTags($bufferedCss);
            $bufferedJsFiles = array_map(fn(array $tags) => $this->_parseExternalResourceTags($tags, 'src'), $bufferedJsFiles);
            $bufferedCssFiles = $this->_parseExternalResourceTags($bufferedCssFiles, 'href');

            if ($saveCache) {
                array_push($cacheValue, $bufferedJs, $bufferedScripts, $bufferedCss, $bufferedJsFiles, $bufferedCssFiles, $bufferedHtml);
            }

            // Re-register the JS and CSS
            $this->_registerResources($bufferedJs, $bufferedScripts, $bufferedCss, $bufferedJsFiles, $bufferedCssFiles, $bufferedHtml);
        }

        if (!$saveCache) {
            return;
        }

        $cacheKey = $this->_cacheKey($key, $global);

        // Normalize duration/expiration into an integer duration
        if ($duration !== null) {
            $expiration = (new DateTime($duration));
        }
        if ($expiration !== null) {
            $duration = DateTimeHelper::toDateTime($expiration)->getTimestamp() - time();
        }

        if ($duration <= 0) {
            $duration = null;
        }

        if ($maxDuration) {
            $duration = $duration ? min($duration, $maxDuration) : $maxDuration;
        }

        /** @phpstan-ignore-next-line */
        Craft::$app->getCache()->set($cacheKey, $cacheValue, $duration, $dep);
    }

    private function _parseInlineResourceTags(array $tags): array
    {
        return array_map(function($tag) {
            $tag = Html::parseTag($tag);
            return [$tag['children'][0]['value'], $tag['attributes']];
        }, $tags);
    }

    private function _parseExternalResourceTags(array $tags, string $urlAttribute): array
    {
        return array_map(function($tag) use ($urlAttribute) {
            [$tag, $condition] = Html::unwrapCondition($tag);
            [$tag, $noscript] = Html::unwrapNoscript($tag);
            $tag = Html::parseTag($tag);
            $url = ArrayHelper::remove($tag['attributes'], $urlAttribute);
            $options = $tag['attributes'];
            if ($condition) {
                $options['condition'] = $condition;
            }
            if ($noscript) {
                $options['noscript'] = true;
            }
            return [$url, $options];
        }, $tags);
    }

    private function _registerResources(
        array $bufferedJs,
        array $bufferedScripts,
        array $bufferedCss,
        array $bufferedJsFiles,
        array $bufferedCssFiles,
        array $bufferedHtml,
    ): void {
        $view = Craft::$app->getView();

        foreach ($bufferedJs as $pos => $scripts) {
            foreach ($scripts as $key => $js) {
                $view->registerJs($js, $pos, $key);
            }
        }

        foreach ($bufferedScripts as $pos => $tags) {
            foreach ($tags as $key => [$script, $options]) {
                $view->registerScript($script, $pos, $options, $key);
            }
        }

        foreach ($bufferedCss as $key => [$css, $options]) {
            $view->registerCss($css, $options, $key);
        }

        foreach ($bufferedJsFiles as $pos => $tags) {
            foreach ($tags as $key => [$url, $options]) {
                $options['position'] = $pos;
                $view->registerJsFile($url, $options, $key);
            }
        }

        foreach ($bufferedCssFiles as $key => [$url, $options]) {
            $view->registerCssFile($url, $options, $key);
        }

        foreach ($bufferedHtml as $pos => $tags) {
            foreach ($tags as $key => $html) {
                $view->registerHtml($html, $pos, $key);
            }
        }
    }

    /**
     * Returns whether template caching is enabled, based on the 'enableTemplateCaching' config setting.
     *
     * @param bool $global Whether this is for a globally-scoped cache
     * @return bool Whether template caching is enabled
     */
    private function _isTemplateCachingEnabled(bool $global): bool
    {
        if (!isset($this->_enabled)) {
            if (!Craft::$app->getConfig()->getGeneral()->enableTemplateCaching) {
                $this->_enabled = $this->_enabledGlobally = false;
            } else {
                // Don't enable template caches for tokenized requests
                $request = Craft::$app->getRequest();
                if ($request->getHadToken()) {
                    $this->_enabled = $this->_enabledGlobally = false;
                } else {
                    $this->_enabled = !$request->getIsConsoleRequest();
                    $this->_enabledGlobally = true;
                }
            }
        }
        return $global ? $this->_enabledGlobally : $this->_enabled;
    }

    /**
     * Defines a data cache key that should be used for a template cache.
     *
     * @param string $key
     * @param bool $global
     * @param int|null $siteId
     * @return string
     * @throws Exception if this is a console request and `false` is passed to `$global`
     */
    private function _cacheKey(string $key, bool $global, ?int $siteId = null): string
    {
        $cacheKey = "template::$key::" . ($siteId ?? Craft::$app->getSites()->getCurrentSite()->id);

        if (!$global) {
            $cacheKey .= '::' . $this->_path();
        }

        return $cacheKey;
    }

    /**
     * Returns the current request path, including a "site:" or "cp:" prefix.
     *
     * @return string
     * @throws Exception if this is a console request
     */
    private function _path(): string
    {
        if (isset($this->_path)) {
            return $this->_path;
        }

        $request = Craft::$app->getRequest();
        if ($request->getIsConsoleRequest()) {
            throw new Exception('Not possible to determine the request path for console commands.');
        }

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_path = 'cp:';
        } else {
            $this->_path = 'site:';
        }

        $this->_path .= Craft::$app->getRequest()->getPathInfo();
        if (Craft::$app->getDb()->getIsMysql()) {
            $this->_path = StringHelper::encodeMb4($this->_path);
        }

        if (($pageNum = Craft::$app->getRequest()->getPageNum()) != 1) {
            $this->_path .= '/' . Craft::$app->getConfig()->getGeneral()->getPageTrigger() . $pageNum;
        }

        return $this->_path;
    }
}
