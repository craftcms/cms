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
use craft\helpers\Db;
use craft\i18n\Translation;
use craft\queue\BaseJob;

/**
 * LocalizeRelations job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class LocalizeRelations extends BaseJob
{
    /**
     * @var int|null The field ID whose data should be localized
     */
    public ?int $fieldId = null;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $relations = (new Query())
            ->select(['id', 'sourceId', 'sourceSiteId', 'targetId', 'sortOrder'])
            ->from([Table::RELATIONS])
            ->where([
                'fieldId' => $this->fieldId,
                'sourceSiteId' => null,
            ])
            ->all();

        $totalRelations = count($relations);
        $allSiteIds = Craft::$app->getSites()->getAllSiteIds();
        $primarySiteId = array_shift($allSiteIds);

        foreach ($relations as $i => $relation) {
            $this->setProgress($queue, $i / $totalRelations);

            // Set the existing relation to the primary site
            Db::update(Table::RELATIONS, [
                'sourceSiteId' => $primarySiteId,
            ], [
                'id' => $relation['id'],
            ]);

            // Duplicate it for the other sites
            foreach ($allSiteIds as $siteId) {
                Db::insert(Table::RELATIONS, [
                    'fieldId' => $this->fieldId,
                    'sourceId' => $relation['sourceId'],
                    'sourceSiteId' => $siteId,
                    'targetId' => $relation['targetId'],
                    'sortOrder' => $relation['sortOrder'],
                ]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Translation::prep('app', 'Localizing relations');
    }
}
