<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\fields\BaseRelationField;
use yii\base\Component;

/**
 * Class Relations service.
 *
 * An instance of the Relations service is globally accessible in Craft via [[Application::relations `Craft::$app->getRelations()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Relations extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Saves some relations for a field.
     *
     * @param BaseRelationField $field
     * @param ElementInterface  $source
     * @param array             $targetIds
     *
     * @throws \Throwable
     * @return void
     */
    public function saveRelations(BaseRelationField $field, ElementInterface $source, array $targetIds)
    {
        /** @var Element $source */
        if (!is_array($targetIds)) {
            $targetIds = [];
        }

        // Prevent duplicate target IDs.
        $targetIds = array_unique($targetIds);

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
                ->delete('{{%relations}}', $oldRelationConditions)
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
                    ->batchInsert('{{%relations}}', $columns, $values)
                    ->execute();
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }
    }
}
