<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementInterface;
use craft\db\Command;
use craft\db\Query;
use craft\db\Table;
use craft\fields\BaseRelationField;
use craft\helpers\Db;
use Throwable;
use yii\base\Component;

/**
 * Relations service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getRelations()|`Craft::$app->relations`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Relations extends Component
{
    /**
     * Saves some relations for a field.
     *
     * @param BaseRelationField $field
     * @param ElementInterface $source
     * @param array $targetIds
     * @throws Throwable
     */
    public function saveRelations(BaseRelationField $field, ElementInterface $source, array $targetIds): void
    {
        if (!is_array($targetIds)) {
            $targetIds = [];
        }

        // Get the unique, indexed target IDs, set to their 0-indexed sort orders
        $targetIds = array_flip(array_values(array_unique(array_filter($targetIds))));

        // Get the current relations
        $oldRelationCondition = ['fieldId' => $field->id, 'sourceId' => $source->id];

        if ($field->localizeRelations) {
            $oldRelationCondition = [
                'and',
                $oldRelationCondition,
                ['or', ['sourceSiteId' => null], ['sourceSiteId' => $source->siteId]],
            ];
        }

        $db = Craft::$app->getDb();

        $oldRelations = (new Query())
            ->select(['id', 'sourceSiteId', 'targetId', 'sortOrder'])
            ->from([Table::RELATIONS])
            ->where($oldRelationCondition)
            ->all($db);

        /** @var Command[] $updateCommands */
        $updateCommands = [];
        $deleteIds = [];

        $sourceSiteId = $field->localizeRelations ? $source->siteId : null;

        foreach ($oldRelations as $relation) {
            // Does this relation still exist?
            if (isset($targetIds[$relation['targetId']])) {
                // Anything to update?
                $sortOrder = $targetIds[$relation['targetId']] + 1;
                // only update relations if the source is not being propagated
                // https://github.com/craftcms/cms/issues/12702
                if ((!$source->propagating && $relation['sourceSiteId'] != $sourceSiteId) || $relation['sortOrder'] != $sortOrder) {
                    $updateCommands[] = $db->createCommand()->update(Table::RELATIONS, [
                        'sourceSiteId' => $sourceSiteId,
                        'sortOrder' => $sortOrder,
                    ], ['id' => $relation['id']]);
                }

                // Avoid re-inserting it
                unset($targetIds[$relation['targetId']]);
            } else {
                $deleteIds[] = $relation['id'];
            }
        }

        if (!empty($updateCommands) || !empty($deleteIds) || !empty($targetIds)) {
            $transaction = $db->beginTransaction();
            try {
                foreach ($updateCommands as $command) {
                    $command->execute();
                }

                // Add the new ones
                if (!empty($targetIds)) {
                    $values = [];
                    foreach ($targetIds as $targetId => $sortOrder) {
                        $values[] = [
                            $field->id,
                            $source->id,
                            $sourceSiteId,
                            $targetId,
                            $sortOrder + 1,
                        ];
                    }
                    Db::batchInsert(Table::RELATIONS, ['fieldId', 'sourceId', 'sourceSiteId', 'targetId', 'sortOrder'], $values, $db);
                }

                if (!empty($deleteIds)) {
                    Db::delete(Table::RELATIONS, [
                        'id' => $deleteIds,
                    ], [], $db);
                }

                $transaction->commit();
            } catch (Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        }
    }
}
