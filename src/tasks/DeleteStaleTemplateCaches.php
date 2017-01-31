<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\tasks;

use Craft;
use craft\base\Task;
use craft\db\Query;
use craft\elements\db\ElementQuery;

/**
 * DeleteStaleTemplateCaches represents a Delete Stale Template Caches background task.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DeleteStaleTemplateCaches extends Task
{
    // Properties
    // =========================================================================

    /**
     * @var int|int[]|null The element ID(s) whose caches need to be cleared
     */
    public $elementId;

    /**
     * @var
     */
    private $_elementType;

    /**
     * @var
     */
    private $_batch;

    /**
     * @var
     */
    private $_batchRows;

    /**
     * @var
     */
    private $_noMoreRows;

    /**
     * @var
     */
    private $_deletedCacheIds;

    /**
     * @var
     */
    private $_totalDeletedCriteriaRows;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTotalSteps(): int
    {
        // What type of element(s) are we dealing with?
        if (is_array($this->elementId)) {
            $this->_elementType = Craft::$app->getElements()->getElementTypesByIds($this->elementId);
        } else {
            $this->_elementType = Craft::$app->getElements()->getElementTypeById($this->elementId);
        }

        if ($this->_elementType === null) {
            return 0;
        }

        // Normalize $elementId
        if (!is_array($this->elementId)) {
            $this->elementId = (array)$this->elementId;
        }

        // Figure out how many rows we're dealing with
        $totalRows = $this->_getQuery()->count('[[id]]');

        if ($totalRows === false) {
            $totalRows = 0;
        }

        $this->_batch = 0;
        $this->_noMoreRows = false;
        $this->_deletedCacheIds = [];
        $this->_totalDeletedCriteriaRows = 0;

        return $totalRows;
    }

    /**
     * @inheritdoc
     */
    public function runStep(int $step)
    {
        // Do we need to grab a fresh batch?
        if (empty($this->_batchRows)) {
            if (!$this->_noMoreRows) {
                $this->_batch++;
                $this->_batchRows = $this->_getQuery()
                    ->select(['cacheId', 'query'])
                    ->orderBy(['id' => SORT_ASC])
                    ->offset(100 * ($this->_batch - 1) - $this->_totalDeletedCriteriaRows)
                    ->limit(100)
                    ->all();

                // Still no more rows?
                if (empty($this->_batchRows)) {
                    $this->_noMoreRows = true;
                }
            }

            if ($this->_noMoreRows) {
                return true;
            }
        }

        $row = array_shift($this->_batchRows);

        // Have we already deleted this cache?
        if (in_array($row['cacheId'], $this->_deletedCacheIds, false)) {
            $this->_totalDeletedCriteriaRows++;
        } else {
            // See if any of the updated elements would get fetched by this query
            /** @var ElementQuery|false $query */
            /** @noinspection UnserializeExploitsInspection - $row['query'] is not user-supplied */
            $query = @unserialize(base64_decode($row['query']));
            if ($query === false || array_intersect($query->ids(), $this->elementId)) {
                // Delete this cache
                Craft::$app->getTemplateCaches()->deleteCacheById($row['cacheId']);
                $this->_deletedCacheIds[] = $row['cacheId'];
                $this->_totalDeletedCriteriaRows++;
            }
        }

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Deleting stale template caches');
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object for selecting criteria that could be dropped by this task.
     *
     * @return Query
     */
    private function _getQuery(): Query
    {
        return (new Query())
            ->from(['{{%templatecachequeries}}'])
            ->where(['type' => $this->_elementType]);
    }
}
