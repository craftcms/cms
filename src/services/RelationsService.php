<?php
namespace Craft;

/**
 * Class RelationsService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class RelationsService extends BaseApplicationComponent
{
	// Public Methods
	// =========================================================================

	/**
	 * Saves some relations for a field.
	 *
	 * @param FieldModel       $field
	 * @param BaseElementModel $source
	 * @param array            $targetIds
	 *
	 * @throws \Exception
	 * @return null
	 */
	public function saveRelations(FieldModel $field, BaseElementModel $source, $targetIds)
	{
		if (!is_array($targetIds))
		{
			$targetIds = array();
		}

		// Prevent duplicate/empty target IDs.
		$targetIds = array_unique(array_filter($targetIds));

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Delete the existing relations
			$oldRelationConditions = array('and', 'fieldId = :fieldId', 'sourceId = :sourceId');
			$oldRelationParams = array(':fieldId' => $field->id, ':sourceId' => $source->id);

			if ($field->translatable)
			{
				$oldRelationConditions[] = array('or', 'sourceLocale is null', 'sourceLocale = :sourceLocale');
				$oldRelationParams[':sourceLocale'] = $source->locale;
			}

			craft()->db->createCommand()->delete('relations', $oldRelationConditions, $oldRelationParams);

			// Add the new ones
			if ($targetIds)
			{
				$values = array();

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
					$values[] = array($field->id, $source->id, $sourceLocale, $targetId, $sortOrder+1);
				}

				$columns = array('fieldId', 'sourceId', 'sourceLocale', 'targetId', 'sortOrder');
				craft()->db->createCommand()->insertAll('relations', $columns, $values);
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
