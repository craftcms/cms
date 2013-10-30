<?php
namespace Craft;

/**
 *
 */
class RelationsService extends BaseApplicationComponent
{
	/**
	 * Saves the relations elements for an element field.
	 *
	 * @param int $fieldId
	 * @param int $sourceId
	 * @param array $targetIds
	 * @throws \Exception
	 */
	public function saveRelations($fieldId, $sourceId, $targetIds)
	{
		// Prevent duplicate child IDs.
		$targetIds = array_unique($targetIds);

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Delete the existing relations
			craft()->db->createCommand()->delete('relations', array(
				'fieldId'  => $fieldId,
				'sourceId' => $sourceId
			));

			if ($targetIds)
			{
				$values = array();

				foreach ($targetIds as $sortOrder => $targetId)
				{
					$values[] = array($fieldId, $sourceId, $targetId, $sortOrder+1);
				}

				$columns = array('fieldId', 'sourceId', 'targetId', 'sortOrder');
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
