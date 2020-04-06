<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\events\DeleteTemplateCachesEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\queue\jobs\DeleteStaleTemplateCaches;
use DateTime;
use yii\base\Component;
use yii\base\Event;
use yii\web\Response;

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
     */
    const EVENT_BEFORE_DELETE_CACHES = 'beforeDeleteCaches';

    /**
     * @event SectionEvent The event that is triggered after template caches are deleted.
     * @since 3.0.2
     */
    const EVENT_AFTER_DELETE_CACHES = 'afterDeleteCaches';

    /**
     * @var string|null The current request's path, as it will be stored in the templatecaches table.
     */
    private $_path;

    /**
     * @var array|null A list of element queries that were executed within the existing caches.
     */
    private $_cachedQueries;

    /**
     * @var array|null A list of element IDs that are active within the existing caches.
     */
    private $_cacheElementIds;

    /**
     * @var bool Whether all caches have been deleted in this request.
     */
    private $_deletedAllCaches = false;

    /**
     * @var bool|null Whether all caches have been deleted, on a per-element type basis, in this request.
     */
    private $_deletedCachesByElementType;

    /**
     * @var int[]|null Index of element IDs to clear caches for in the Delete Stale Template Caches job
     */
    private $_deleteCachesIndex;

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

        // Don't return anything if it's not a global request and it is a console request.
        if (!$global && Craft::$app->getRequest()->getIsConsoleRequest()) {
            return null;
        }

        // Don't return anything if it's not a global request and the path > 255 characters.
        if (!$global && strlen($this->_getPath()) > 255) {
            return null;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $query = (new Query())
            ->select(['body'])
            ->from([Table::TEMPLATECACHES])
            ->where([
                'and',
                [
                    'cacheKey' => $key,
                    'siteId' => Craft::$app->getSites()->getCurrentSite()->id
                ],
                ['>', 'expiryDate', Db::prepareDateForDb(new \DateTime())],
            ]);

        if (!$global) {
            $query->andWhere([
                'path' => $this->_getPath()
            ]);
        }

        $cachedBody = $query->scalar();

        if ($cachedBody === false) {
            return null;
        }

        return $cachedBody;
    }

    /**
     * Starts a new template cache.
     *
     * @param string $key The template cache key.
     */
    public function startTemplateCache(string $key)
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled() === false) {
            return;
        }

        // Is this the first time we've started caching?
        if ($this->_cachedQueries === null) {
            Event::on(ElementQuery::class, ElementQuery::EVENT_AFTER_PREPARE, [
                $this,
                'includeElementQueryInTemplateCaches'
            ]);
        }

        if (Craft::$app->getConfig()->getGeneral()->cacheElementQueries) {
            $this->_cachedQueries[$key] = [];
        }

        $this->_cacheElementIds[$key] = [];
    }

    /**
     * Includes an element criteria in any active caches.
     *
     * @param Event $event The 'afterPrepare' element query event
     */
    public function includeElementQueryInTemplateCaches(Event $event)
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled() === false) {
            return;
        }

        if (!empty($this->_cachedQueries)) {
            /** @var ElementQuery $elementQuery */
            $elementQuery = $event->sender;
            $query = $elementQuery->query;
            $subQuery = $elementQuery->subQuery;
            $customFields = $elementQuery->customFields;
            $elementQuery->query = null;
            $elementQuery->subQuery = null;
            $elementQuery->customFields = null;
            // We need to base64-encode the string so db\Connection::quoteSql() doesn't tweak any of the table/columns names
            $serialized = base64_encode(serialize($elementQuery));
            $elementQuery->query = $query;
            $elementQuery->subQuery = $subQuery;
            $elementQuery->customFields = $customFields;
            $hash = md5($serialized);

            foreach ($this->_cachedQueries as &$queries) {
                $queries[$hash] = [
                    $elementQuery->elementType,
                    $serialized
                ];
            }
            unset($queries);
        }
    }

    /**
     * Includes an element in any active caches.
     *
     * @param int $elementId The element ID.
     */
    public function includeElementInTemplateCaches(int $elementId)
    {
        // Make sure template caching is enabled
        if ($this->_isTemplateCachingEnabled() === false) {
            return;
        }

        if (!empty($this->_cacheElementIds)) {
            foreach ($this->_cacheElementIds as &$elementIds) {
                if (!in_array($elementId, $elementIds, false)) {
                    $elementIds[] = $elementId;
                }
            }
            unset($elementIds);
        }
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

        // Don't return anything if it's not a global request and it's a console request.
        if (!$global && Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        // If there are any transform generation URLs in the body, don't cache it.
        // stripslashes($body) in case the URL has been JS-encoded or something.
        if (StringHelper::contains(stripslashes($body), 'assets/generate-transform')) {
            return;
        }

        if (!$global && (strlen($path = $this->_getPath()) > 255)) {
            Craft::warning('Skipped adding ' . $key . ' to template cache table because the path is > 255 characters: ' . $path, __METHOD__);

            return;
        }

        if (Craft::$app->getDb()->getIsMysql()) {
            // Encode any 4-byte UTF-8 characters
            $body = StringHelper::encodeMb4($body);
        }

        // Figure out the expiration date
        if ($duration !== null) {
            $expiration = new DateTime($duration);
        }

        if (!$expiration) {
            $cacheDuration = Craft::$app->getConfig()->getGeneral()->cacheDuration;

            if ($cacheDuration <= 0) {
                $cacheDuration = 31536000; // 1 year
            }

            $cacheDuration += time();

            $expiration = new DateTime('@' . $cacheDuration);
        }

        // Save it
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            Craft::$app->getDb()->createCommand()
                ->insert(
                    Table::TEMPLATECACHES,
                    [
                        'cacheKey' => $key,
                        'siteId' => Craft::$app->getSites()->getCurrentSite()->id,
                        'path' => $global ? null : $this->_getPath(),
                        'expiryDate' => Db::prepareDateForDb($expiration),
                        'body' => $body
                    ],
                    false)
                ->execute();

            $cacheId = Craft::$app->getDb()->getLastInsertID(Table::TEMPLATECACHES);

            // Tag it with any element queries that were executed within the cache
            if (!empty($this->_cachedQueries[$key])) {
                $values = [];
                foreach ($this->_cachedQueries[$key] as $query) {
                    $values[] = [
                        $cacheId,
                        $query[0],
                        $query[1]
                    ];
                    // We can no longer say we’ve deleted all template caches for this element type
                    unset($this->_deletedCachesByElementType[$query[0]]);
                }
                Craft::$app->getDb()->createCommand()
                    ->batchInsert(Table::TEMPLATECACHEQUERIES, [
                        'cacheId',
                        'type',
                        'query'
                    ], $values, false)
                    ->execute();
                unset($this->_cachedQueries[$key]);
            }

            // Tag it with any element IDs that were output within the cache
            if (!empty($this->_cacheElementIds[$key])) {
                $values = [];

                foreach ($this->_cacheElementIds[$key] as $elementId) {
                    $values[] = [$cacheId, $elementId];
                }

                Craft::$app->getDb()->createCommand()
                    ->batchInsert(
                        Table::TEMPLATECACHEELEMENTS,
                        ['cacheId', 'elementId'],
                        $values,
                        false)
                    ->execute();

                unset($this->_cacheElementIds[$key]);
            }

            // We can no longer say we’ve deleted all template caches
            $this->_deletedAllCaches = false;

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    /**
     * Deletes a cache by its ID(s).
     *
     * @param int|int[] $cacheId The cache ID(s)
     * @return bool
     */
    public function deleteCacheById($cacheId): bool
    {
        if (is_array($cacheId) && empty($cacheId)) {
            return false;
        }

        if ($this->_deletedAllCaches) {
            return false;
        }

        // Fire a 'beforeDeleteCaches' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_CACHES)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_CACHES, new DeleteTemplateCachesEvent([
                'cacheIds' => (array)$cacheId
            ]));
        }

        $affectedRows = Craft::$app->getDb()->createCommand()
            ->delete(Table::TEMPLATECACHES, ['id' => $cacheId])
            ->execute();

        // Fire an 'afterDeleteCaches' event
        if ($affectedRows && $this->hasEventHandlers(self::EVENT_AFTER_DELETE_CACHES)) {
            $this->trigger(self::EVENT_AFTER_DELETE_CACHES, new DeleteTemplateCachesEvent([
                'cacheIds' => (array)$cacheId
            ]));
        }

        return (bool)$affectedRows;
    }

    /**
     * Deletes caches by a given element class.
     *
     * @param string $elementType The element class.
     * @return bool
     */
    public function deleteCachesByElementType(string $elementType): bool
    {
        if ($this->_deletedAllCaches || isset($this->_deletedCachesByElementType[$elementType])) {
            return false;
        }

        $cacheIds = (new Query())
            ->select(['cacheId'])
            ->from([Table::TEMPLATECACHEQUERIES])
            ->where(['type' => $elementType])
            ->column();

        $success = $this->deleteCacheById($cacheIds);
        $this->_deletedCachesByElementType[$elementType] = true;
        return $success;
    }

    /**
     * Deletes caches that include a given element(s).
     *
     * @param ElementInterface|ElementInterface[] $elements The element(s) whose caches should be deleted.
     * @return bool
     */
    public function deleteCachesByElement($elements): bool
    {
        if ($this->_deletedAllCaches || empty($elements)) {
            return false;
        }

        if (is_array($elements)) {
            $firstElement = reset($elements);
        } else {
            $firstElement = $elements;
            $elements = [$elements];
        }

        $elementType = get_class($firstElement);
        $deleteQueryCaches = !isset($this->_deletedCachesByElementType[$elementType]);
        $elementIds = [];

        /** @var Element[] $elements */
        foreach ($elements as $element) {
            $elementIds[] = $element->id;
        }

        return $this->deleteCachesByElementId($elementIds, $deleteQueryCaches);
    }

    /**
     * Deletes caches that include an a given element ID(s).
     *
     * @param int|int[] $elementId The ID of the element(s) whose caches should be cleared.
     * @param bool $deleteQueryCaches Whether a DeleteStaleTemplateCaches job
     * should be added to the queue, deleting any query caches that may now
     * involve this element, but hadn't previously. (Defaults to `true`.)
     * @return bool
     */
    public function deleteCachesByElementId($elementId, bool $deleteQueryCaches = true): bool
    {
        if ($this->_deletedAllCaches || !$elementId) {
            return false;
        }

        // Check the query caches too?
        if (
            $deleteQueryCaches &&
            $this->_isTemplateCachingEnabled() &&
            Craft::$app->getConfig()->getGeneral()->cacheElementQueries
        ) {
            if ($this->_deleteCachesIndex === null) {
                Craft::$app->getResponse()->on(Response::EVENT_AFTER_PREPARE, [$this, 'handleResponse']);
                $this->_deleteCachesIndex = [];
            }
            if (is_array($elementId)) {
                foreach ($elementId as $id) {
                    $this->_deleteCachesIndex[$id] = true;
                }
            } else {
                $this->_deleteCachesIndex[$elementId] = true;
            }
        }

        $cacheIds = (new Query())
            ->select(['cacheId'])
            ->distinct(true)
            ->from([Table::TEMPLATECACHEELEMENTS])
            ->where(['elementId' => $elementId])
            ->column();

        return $this->deleteCacheById($cacheIds);
    }

    /**
     * Queues up a Delete Stale Template Caches job
     */
    public function handleResponse()
    {
        // It's possible this is already null
        if ($this->_deleteCachesIndex !== null) {
            Craft::$app->getQueue()->push(new DeleteStaleTemplateCaches([
                'elementId' => array_keys($this->_deleteCachesIndex),
            ]));

            $this->_deleteCachesIndex = null;
        }
    }

    /**
     * Deletes caches that include elements that match a given element query's parameters.
     *
     * @param ElementQuery $query The element query that should be used to find elements whose caches
     * should be deleted.
     * @return bool
     */
    public function deleteCachesByElementQuery(ElementQuery $query): bool
    {
        if ($this->_deletedAllCaches) {
            return false;
        }

        $limit = $query->limit;
        $query->limit(null);
        $elementIds = $query->ids();
        $query->limit($limit);

        return $this->deleteCachesByElementId($elementIds);
    }

    /**
     * Deletes a cache by its key(s).
     *
     * @param int|array $key The cache key(s) to delete.
     * @return bool
     */
    public function deleteCachesByKey($key): bool
    {
        if ($this->_deletedAllCaches) {
            return false;
        }

        $cacheIds = (new Query())
            ->select(['id'])
            ->from([Table::TEMPLATECACHES])
            ->where(['cacheKey' => $key])
            ->column();

        return $this->deleteCacheById($cacheIds);
    }

    /**
     * Deletes any expired caches.
     *
     * @return bool
     */
    public function deleteExpiredCaches(): bool
    {
        if ($this->_deletedAllCaches) {
            return false;
        }

        $cacheIds = (new Query())
            ->select(['id'])
            ->from([Table::TEMPLATECACHES])
            ->where(['<=', 'expiryDate', Db::prepareDateForDb(new \DateTime())])
            ->column();

        return $this->deleteCacheById($cacheIds);
    }

    /**
     * Deletes any expired caches.
     *
     * @return bool
     * @deprecated in 3.2.0. Use [[deleteExpiredCaches()]] instead.
     */
    public function deleteExpiredCachesIfOverdue(): bool
    {
        return $this->deleteExpiredCaches();
    }

    /**
     * Deletes all the template caches.
     *
     * @return bool
     */
    public function deleteAllCaches(): bool
    {
        if ($this->_deletedAllCaches) {
            return false;
        }

        $cacheIds = (new Query())
            ->select(['id'])
            ->from([Table::TEMPLATECACHES])
            ->column();

        $success = $this->deleteCacheById($cacheIds);
        $this->_deletedAllCaches = true;
        return $success;
    }

    /**
     * Returns whether template caching is enabled, based on the 'enableTemplateCaching' config setting.
     *
     * @return bool Whether template caching is enabled
     */
    private function _isTemplateCachingEnabled(): bool
    {
        if (Craft::$app->getConfig()->getGeneral()->enableTemplateCaching) {
            return true;
        }

        return false;
    }

    /**
     * Returns the current request path, including a "site:" or "cp:" prefix.
     *
     * @return string
     */
    private function _getPath(): string
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
