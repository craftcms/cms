<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\helpers\StringHelper;
use DateTime;
use yii\base\Component;
use yii\base\Event;

/**
 * Template Caches service.
 * An instance of the Template Caches service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getTemplateCaches()|`Craft::$app->templateCaches`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class TemplateCaches extends Component
{
    /**
     * @event SectionEvent The event that is triggered before template caches are deleted.
     * @since 3.0.2
     * @deprecated in 3.5.0
     */
    const EVENT_BEFORE_DELETE_CACHES = 'beforeDeleteCaches';

    /**
     * @event SectionEvent The event that is triggered after template caches are deleted.
     * @since 3.0.2
     * @deprecated in 3.5.0
     */
    const EVENT_AFTER_DELETE_CACHES = 'afterDeleteCaches';

    /**
     * @var bool Whether template caching should be enabled for this request
     * @see _isTemplateCachingEnabled()
     */
    private $_enabled;

    /**
     * @var string|null The current request's path
     * @see _path()
     */
    private $_path;

    /**
     * Returns a cached template by its key.
     *
     * @param string $key The template cache key
     * @param bool $global Whether the cache would have been stored globally.
     * @return string|null
     */
    public function getTemplateCache(string $key, bool $global)
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

        list($body, $tags) = $data;

        // If we're actively collecting element cache tags, add this cache's tags to the collection
        Craft::$app->getElements()->collectCacheTags($tags);
        return $body;
    }

    /**
     * Starts a new template cache.
     */
    public function startTemplateCache()
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled() === false) {
            return;
        }

        Craft::$app->getElements()->startCollectingCacheTags();
    }

    /**
     * Includes an element criteria in any active caches.
     *
     * @param Event $event The 'afterPrepare' element query event
     * @deprecated in 3.5.0
     */
    public function includeElementQueryInTemplateCaches(Event $event)
    {
    }

    /**
     * Includes an element in any active caches.
     *
     * @param int $elementId The element ID.
     * @deprecated in 3.5.0
     */
    public function includeElementInTemplateCaches(int $elementId)
    {
    }

    /**
     * Ends a template cache.
     *
     * @param string $key The template cache key.
     * @param bool $global Whether the cache should be stored globally.
     * @param string|null $duration How long the cache should be stored for. Should be a [relative time format](http://php.net/manual/en/datetime.formats.relative.php).
     * @param mixed|null $expiration When the cache should expire.
     * @param string $body The contents of the cache.
     * @throws \Throwable
     */
    public function endTemplateCache(string $key, bool $global, string $duration = null, $expiration, string $body)
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled() === false) {
            return;
        }

        $dep = Craft::$app->getElements()->stopCollectingCacheTags();

        // Always add a `template` tag
        $dep->tags[] = 'template';

        // If there are any transform generation URLs in the body, don't cache it.
        // stripslashes($body) in case the URL has been JS-encoded or something.
        if (StringHelper::contains(stripslashes($body), 'assets/generate-transform')) {
            return;
        }

        $cacheKey = $this->_cacheKey($key, $global);
        if ($duration !== null) {
            $duration = (new DateTime($duration))->getTimestamp() - time();
        }
        Craft::$app->getCache()->set($cacheKey, [$body, $dep->tags], $duration, $dep);
    }

    /**
     * Deletes a cache by its ID(s).
     *
     * @param int|int[] $cacheId The cache ID(s)
     * @return bool
     * @deprecated in 3.5.0
     */
    public function deleteCacheById($cacheId): bool
    {
        return false;
    }

    /**
     * Deletes caches by a given element class.
     *
     * @param string $elementType The element class.
     * @return bool
     * @deprecated in 3.5.0. Use [[\craft\services\Elements::invalidateCachesForElementType()]] instead.
     */
    public function deleteCachesByElementType(string $elementType): bool
    {
        Craft::$app->getElements()->invalidateCachesForElementType($elementType);
        return true;
    }

    /**
     * Deletes caches that include a given element(s).
     *
     * @param ElementInterface|ElementInterface[] $elements The element(s) whose caches should be deleted.
     * @return bool
     * @deprecated in 3.5.0. Use [[\craft\services\Elements::invalidateCachesForElement()]] instead.
     */
    public function deleteCachesByElement($elements): bool
    {
        $elementsService = Craft::$app->getElements();
        if (is_array($elements)) {
            foreach ($elements as $element) {
                $elementsService->invalidateCachesForElement($element);
            }
        } else {
            $elementsService->invalidateCachesForElement($elements);
        }
        return true;
    }

    /**
     * Deletes caches that include an a given element ID(s).
     *
     * @param int|int[] $elementId The ID of the element(s) whose caches should be cleared.
     * @param bool $deleteQueryCaches Whether a DeleteStaleTemplateCaches job
     * should be added to the queue, deleting any query caches that may now
     * involve this element, but hadn't previously. (Defaults to `true`.)
     * @return bool
     * @deprecated in 3.5.0. Use [[\craft\services\Elements::invalidateCachesForElement()]] instead.
     */
    public function deleteCachesByElementId($elementId, bool $deleteQueryCaches = true): bool
    {
        $elementsService = Craft::$app->getElements();
        $element = Craft::$app->getElements()->getElementById($elementId);
        if (!$element) {
            return false;
        }
        $elementsService->invalidateCachesForElement($element);
        return true;
    }

    /**
     * Queues up a Delete Stale Template Caches job
     *
     * @deprecated in 3.5.0
     */
    public function handleResponse()
    {
    }

    /**
     * Deletes caches that include elements that match a given element query's parameters.
     *
     * @param ElementQuery $query The element query that should be used to find elements whose caches
     * should be deleted.
     * @return bool
     * @deprecated in 3.5.0. Use [[\craft\services\Elements::invalidateCachesForElementType()]] instead.
     */
    public function deleteCachesByElementQuery(ElementQuery $query): bool
    {
        if (!$query->elementType) {
            return false;
        }
        Craft::$app->getElements()->invalidateCachesForElementType($query->elementType);
        return true;
    }

    /**
     * Deletes a cache by its key(s).
     *
     * @param int|array $key The cache key(s) to delete.
     * @return bool
     * @deprecated in 3.5.0
     */
    public function deleteCachesByKey($key): bool
    {
        $cache = Craft::$app->getCache();
        // ¯\_(ツ)_/¯
        $cache->delete($this->_cacheKey($key, true));
        $cache->delete($this->_cacheKey($key, false));
        return true;
    }

    /**
     * Deletes any expired caches.
     *
     * @return bool
     * @deprecated in 3.5.0
     */
    public function deleteExpiredCaches(): bool
    {
        return true;
    }

    /**
     * Deletes any expired caches.
     *
     * @return bool
     * @deprecated in 3.2.0
     */
    public function deleteExpiredCachesIfOverdue(): bool
    {
        return true;
    }

    /**
     * Deletes all the template caches.
     *
     * @return bool
     * @deprecated in 3.5.0. Use [[\craft\services\Elements::invalidateAllCaches()]] instead.
     */
    public function deleteAllCaches(): bool
    {
        Craft::$app->getElements()->invalidateAllCaches();
        return true;
    }

    /**
     * Returns whether template caching is enabled, based on the 'enableTemplateCaching' config setting.
     *
     * @return bool Whether template caching is enabled
     */
    private function _isTemplateCachingEnabled(): bool
    {
        if ($this->_enabled === null) {
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
     */
    private function _cacheKey(string $key, bool $global): string
    {
        $cacheKey = "template::$key::" . Craft::$app->getSites()->getCurrentSite()->id;

        if (!$global) {
            $cacheKey .= '::' . $this->_path();
        }

        return $cacheKey;
    }

    /**
     * Returns the current request path, including a "site:" or "cp:" prefix.
     *
     * @return string
     */
    private function _path(): string
    {
        if ($this->_path !== null) {
            return $this->_path;
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
