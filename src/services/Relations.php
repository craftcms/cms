<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\fields\BaseRelationField;
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
     * @throws \Exception
     * @return boolean
     */
    public function saveRelations(BaseRelationField $field, ElementInterface $source, $targetIds)
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
                'fieldId = :fieldId',
                'sourceId = :sourceId'
            ];
            $oldRelationParams = [
                ':fieldId' => $field->id,
                ':sourceId' => $source->id
            ];

            if ($field->localizeRelations) {
                $oldRelationConditions[] = [
                    'or',
                    'sourceSiteId is null',
                    'sourceSiteId = :sourceSiteId'
                ];
                $oldRelationParams[':sourceSiteId'] = $source->siteId;
            }

            Craft::$app->getDb()->createCommand()
                ->delete('{{%relations}}', $oldRelationConditions, $oldRelationParams)
                ->execute();

            // Add the new ones
            if ($targetIds) {
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
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }
    }
}
