<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\ElementInterface;
use craft\app\fields\BaseRelationField;
use yii\base\Component;

/**
 * Class Relations service.
 *
 * An instance of the Relations service is globally accessible in Craft via [[Application::relations `Craft::$app->getRelations()`]].
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
	 * @param BaseRelationField     $field
	 * @param ElementInterface $source
	 * @param array            $targetIds
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function saveRelations(BaseRelationField $field, ElementInterface $source, $targetIds)
	{
		if (!is_array($targetIds))
		{
			$targetIds = [];
		}

		// Prevent duplicate target IDs.
		$targetIds = array_unique($targetIds);

		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			// Delete the existing relations
			$oldRelationConditions = ['and', 'fieldId = :fieldId', 'sourceId = :sourceId'];
			$oldRelationParams = [':fieldId' => $field->id, ':sourceId' => $source->id];

			if ($field->translatable)
			{
				$oldRelationConditions[] = ['or', 'sourceLocale is null', 'sourceLocale = :sourceLocale'];
				$oldRelationParams[':sourceLocale'] = $source->locale;
			}

			Craft::$app->getDb()->createCommand()->delete('{{%relations}}', $oldRelationConditions, $oldRelationParams)->execute();

			// Add the new ones
			if ($targetIds)
			{
				$values = [];

				if ($field->translatable)
				{
					$sourceLocale = $source->locale;
				}
				else
				{
					$sourceLocale = null;
				}

				foreach ($targetIds as $sortOrder => $targetId)
				{
					$values[] = [$field->id, $source->id, $sourceLocale, $targetId, $sortOrder+1];
				}

				$columns = ['fieldId', 'sourceId', 'sourceLocale', 'targetId', 'sortOrder'];
				Craft::$app->getDb()->createCommand()->batchInsert('{{%relations}}', $columns, $values)->execute();
			}

			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}
}
