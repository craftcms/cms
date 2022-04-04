<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use DateTime;
use Throwable;
use yii\base\Component;
use yii\base\Exception;

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
     * @var string|null The current request's path
     * @see _path()
     */
    private ?string $_path = null;

    /**
     * Returns a cached template by its key.
     *
     * @param string $key The template cache key
     * @param bool $global Whether the cache would have been stored globally.
     * @param bool $registerScripts Whether JS and CSS code coptured with the cache should be registered
     * @return string|null
     * @throws Exception if this is a console request and `false` is passed to `$global`
     */
    public function getTemplateCache(string $key, bool $global, bool $registerScripts = false): ?string
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled() === false) {
            return null;
        }

        $cacheKey = $this->_cacheKey($key, $global);
        $data = Craft::$app->getCache()->get($cacheKey);

        if ($data === false) {
            return null;
        }

        [$body, $tags, $bufferedJs, $bufferedScripts, $bufferedCss] = array_pad($data, 5, null);

        // If we're actively collecting element cache tags, add this cache's tags to the collection
        Craft::$app->getElements()->collectCacheTags($tags);

        // Register JS and CSS tags
        if ($registerScripts) {
            $this->_registerScripts($bufferedJs ?? [], $bufferedScripts ?? [], $bufferedCss ?? []);
        }

        return $body;
    }

    /**
     * Starts a new template cache.
     *
     * @param bool $withScripts Whether JS and CSS code registered with [[\craft\web\View::registerJs()]],
     * [[\craft\web\View::registerScript()]], and [[\craft\web\View::registerCss()]] should be captured and
     * included in the cache. If this is `true`, be sure to pass `$withScripts = true` to [[endTemplateCache()]]
     * as well.
     */
    public function startTemplateCache(bool $withScripts = false): void
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled() === false) {
            return;
        }

        Craft::$app->getElements()->startCollectingCacheTags();

        if ($withScripts) {
            $view = Craft::$app->getView();
            $view->startJsBuffer();
            $view->startScriptBuffer();
            $view->startCssBuffer();
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
     * @param bool $withScripts Whether JS and CSS code registered with [[\craft\web\View::registerJs()]],
     * [[\craft\web\View::registerScript()]], and [[\craft\web\View::registerCss()]] should be captured and
     * included in the cache.
     * @throws Exception if this is a console request and `false` is passed to `$global`
     * @throws Throwable
     */
    public function endTemplateCache(string $key, bool $global, ?string $duration, mixed $expiration, string $body, bool $withScripts = false): void
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled() === false) {
            return;
        }

        $dep = Craft::$app->getElements()->stopCollectingCacheTags();

        if ($withScripts) {
            $view = Craft::$app->getView();
            $bufferedJs = $view->clearJsBuffer(false, false);
            $bufferedScripts = $view->clearScriptBuffer();
            $bufferedCss = $view->clearCssBuffer();
        }

        // If there are any transform generation URLs in the body, don't cache it.
        // stripslashes($body) in case the URL has been JS-encoded or something.
        if (StringHelper::contains(stripslashes($body), 'assets/generate-transform')) {
            return;
        }

        // Always add a `template` tag
        $dep->tags[] = 'template';

        $cacheValue = [$body, $dep->tags];

        if ($withScripts) {
            // Parse the JS/CSS code and tag attributes out of the <script> and <style> tags
            $bufferedScripts = array_map(function($tags) {
                return array_map(function($tag) {
                    $tag = Html::parseTag($tag);
                    return [$tag['children'][0]['value'], $tag['attributes']];
                }, $tags);
            }, $bufferedScripts);
            $bufferedCss = array_map(function($tag) {
                $tag = Html::parseTag($tag);
                return [$tag['children'][0]['value'], $tag['attributes']];
            }, $bufferedCss);

            array_push($cacheValue, $bufferedJs, $bufferedScripts, $bufferedCss);

            // Re-register the JS and CSS
            $this->_registerScripts($bufferedJs, $bufferedScripts, $bufferedCss);
        }

        $cacheKey = $this->_cacheKey($key, $global);

        if ($duration !== null) {
            $expiration = (new DateTime($duration));
        }

        if ($expiration !== null) {
            $duration = DateTimeHelper::toDateTime($expiration)->getTimestamp() - time();
        }

        Craft::$app->getCache()->set($cacheKey, $cacheValue, $duration, $dep);
    }

    private function _registerScripts(array $bufferedJs, array $bufferedScripts, array $bufferedCss): void
    {
        $view = Craft::$app->getView();

        foreach ($bufferedJs as $pos => $scripts) {
            foreach ($scripts as $key => $script) {
                $view->registerJs($script, $pos, $key);
            }
        }

        foreach ($bufferedScripts as $pos => $tags) {
            foreach ($tags as $key => $tag) {
                [$script, $options] = $tag;
                $view->registerScript($script, $pos, $options, $key);
            }
        }

        foreach ($bufferedCss as $key => $tag) {
            [$css, $options] = $tag;
            $view->registerCss($css, $options, $key);
        }
    }

    /**
     * Returns whether template caching is enabled, based on the 'enableTemplateCaching' config setting.
     *
     * @return bool Whether template caching is enabled
     */
    private function _isTemplateCachingEnabled(): bool
    {
        if (!isset($this->_enabled)) {
            $this->_enabled = (
                Craft::$app->getConfig()->getGeneral()->enableTemplateCaching &&
                !Craft::$app->getRequest()->getIsConsoleRequest()
            );
        }
        return $this->_enabled;
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
