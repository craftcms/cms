<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\queue\BaseJob;

/**
 * DeleteStaleTemplateCaches job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DeleteStaleTemplateCaches extends BaseJob
{
    // Properties
    // =========================================================================

    /**
     * @var int|int[]|null The element ID(s) whose caches need to be cleared
     */
    public $elementId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        // What type of element(s) are we dealing with?
        if (is_array($this->elementId)) {
            $elementType = Craft::$app->getElements()->getElementTypesByIds($this->elementId);
        } else if ($this->elementId) {
            $elementType = Craft::$app->getElements()->getElementTypeById($this->elementId);
        }

        if (empty($elementType)) {
            return;
        }

        // Normalize $elementId
        if (!is_array($this->elementId)) {
            $this->elementId = (array)$this->elementId;
        }

        // Delete any expired template caches
        $templateCachesService = Craft::$app->getTemplateCaches();
        $templateCachesService->deleteExpiredCaches();

        $query = (new Query())
            ->select(['cacheId', 'query'])
            ->from([Table::TEMPLATECACHEQUERIES])
            ->where(['type' => $elementType])
            ->orderBy(['id' => SORT_ASC]);

        // Figure out how many rows we're dealing with
        $totalRows = $query->count('[[id]]');

        if (!$totalRows) {
            return;
        }

        $currentRow = 0;
        $deleteCacheIds = [];

        foreach ($query->each() as $row) {

            $this->setProgress($queue, $currentRow / $totalRows, Craft::t('app', '{step} of {total}', [
                'step' => $currentRow + 1,
                'total' => $totalRows,
            ]));
            $currentRow++;

            // Do we already plan on deleting this cache?
            if (isset($deleteCacheIds[$row['cacheId']])) {
                continue;
            }

            // See if any of the updated elements would get fetched by this query
            /** @var ElementQuery|false $query */
            /** @noinspection UnserializeExploitsInspection - $row['query'] is not user-supplied */
            $query = @unserialize(base64_decode($row['query']));
            if ($query === false || array_intersect($query->ids(), $this->elementId)) {
                $deleteCacheIds[$row['cacheId']] = true;
            }
        }

        // Actually delete the caches now
        if (!empty($deleteCacheIds)) {
            $templateCachesService->deleteCacheById(array_keys($deleteCacheIds));
        }
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
}
