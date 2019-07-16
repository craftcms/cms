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
use craft\db\Table;
use craft\fields\BaseRelationField;
use yii\base\Component;

/**
 * Relations service.
 * An instance of the Relations service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getRelations()|`Craft::$app->relations`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Relations extends Component
{
    // Public Methods
    // =========================================================================

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

        // Prevent duplicate/empty target IDs.
        $targetIds = array_unique(array_filter($targetIds));

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Delete the existing relations
            $oldRelationConditions = [
                'and',
                [
                    'fieldId' => $field->id,
                    'sourceId' => $source->id,
                ]
            ];

            if ($field->localizeRelations) {
                $oldRelationConditions[] = [
                    'or',
                    ['sourceSiteId' => null],
                    ['sourceSiteId' => $source->siteId]
                ];
            }

            Craft::$app->getDb()->createCommand()
                ->delete(Table::RELATIONS, $oldRelationConditions)
                ->execute();

            // Add the new ones
            if (!empty($targetIds)) {
                $values = [];

                if ($field->localizeRelations) {
                    $sourceSiteId = $source->siteId;
                } else {
                    $sourceSiteId = null;
                }

                foreach ($targetIds as $sortOrder => $targetId) {
                    $values[] = [
                        $field->id,
                        $source->id,
                        $sourceSiteId,
                        $targetId,
                        $sortOrder + 1
                    ];
                }

                $columns = [
                    'fieldId',
                    'sourceId',
                    'sourceSiteId',
                    'targetId',
                    'sortOrder'
                ];
                Craft::$app->getDb()->createCommand()
                    ->batchInsert(Table::RELATIONS, $columns, $values)
                    ->execute();
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }
    }
}
