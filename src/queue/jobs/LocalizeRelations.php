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
use craft\queue\BaseJob;

/**
 * LocalizeRelations job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class LocalizeRelations extends BaseJob
{
    // Properties
    // =========================================================================

    /**
     * @var int|null The field ID whose data should be localized
     */
    public $fieldId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $relations = (new Query())
            ->select(['id', 'sourceId', 'sourceSiteId', 'targetId', 'sortOrder'])
            ->from([Table::RELATIONS])
            ->where([
                'fieldId' => $this->fieldId,
                'sourceSiteId' => null
            ])
            ->all();

        $totalRelations = count($relations);
        $allSiteIds = Craft::$app->getSites()->getAllSiteIds();
        $primarySiteId = array_shift($allSiteIds);
        $db = Craft::$app->getDb();

        foreach ($relations as $i => $relation) {
            $this->setProgress($queue, $i / $totalRelations);

            // Set the existing relation to the primary site
            $db->createCommand()
                ->update(
                    Table::RELATIONS,
                    ['sourceSiteId' => $primarySiteId],
                    ['id' => $relation['id']])
                ->execute();

            // Duplicate it for the other sites
            foreach ($allSiteIds as $siteId) {
                $db->createCommand()
                    ->insert(
                        Table::RELATIONS,
                        [
                            'fieldid' => $this->fieldId,
                            'sourceId' => $relation['sourceId'],
                            'sourceSiteId' => $siteId,
                            'targetId' => $relation['targetId'],
                            'sortOrder' => $relation['sortOrder'],
                        ])
                    ->execute();
            }
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Localizing relations');
    }
}
