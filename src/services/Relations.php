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
use craft\db\Command;
use craft\db\Query;
use craft\db\Table;
use craft\fields\BaseRelationField;
use yii\base\Component;

/**
 * Relations service.
 * An instance of the Relations service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getRelations()|`Craft::$app->relations`]].
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
     * @throws \Throwable
     */
    public function saveRelations(BaseRelationField $field, ElementInterface $source, array $targetIds)
    {
        /** @var Element $source */
        if (!is_array($targetIds)) {
            $targetIds = [];
        }

        // Get the unique, indexed target IDs, set to their 0-indexed sort orders
        $targetIds = array_flip(array_values(array_unique(array_filter($targetIds))));

        // Get the current relations
        $oldRelationConditions = ['fieldId' => $field->id, 'sourceId' => $source->id];

        if ($field->localizeRelations) {
            $oldRelationConditions = [
                'and',
                $oldRelationConditions,
                ['or', ['sourceSiteId' => null], ['sourceSiteId' => $source->siteId]],
            ];
        }

        $oldRelations = (new Query())
            ->select(['id', 'sourceSiteId', 'targetId', 'sortOrder'])
            ->from([Table::RELATIONS])
            ->where($oldRelationConditions)
            ->all();

        /** @var Command[] $updateCommands */
        $updateCommands = [];
        $deleteIds = [];

        $sourceSiteId = $field->localizeRelations ? $source->siteId : null;
        $db = Craft::$app->getDb();

        foreach ($oldRelations as $relation) {
            // Does this relation still exist?
            if (isset($targetIds[$relation['targetId']])) {
                // Anything to update?
                $sortOrder = $targetIds[$relation['targetId']] + 1;
                if ($relation['sourceSiteId'] != $sourceSiteId || $relation['sortOrder'] != $sortOrder) {
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
            $transaction = Craft::$app->getDb()->beginTransaction();
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
                    $db->createCommand()
                        ->batchInsert(Table::RELATIONS, ['fieldId', 'sourceId', 'sourceSiteId', 'targetId', 'sortOrder'], $values)
                        ->execute();
                }

                if (!empty($deleteIds)) {
                    $db->createCommand()
                        ->delete(Table::RELATIONS, ['id' => $deleteIds])
                        ->execute();
                }

                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();

                throw $e;
            }
        }
    }
}
