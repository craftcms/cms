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
	 * @param int $parentId
	 * @param array $childIds
	 * @throws \Exception
	 */
	public function saveRelations($fieldId, $parentId, $childIds)
	{
		// Prevent duplicate child IDs.
		$childIds = array_unique($childIds);

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Delete the existing relations
			craft()->db->createCommand()->delete('relations', array(
				'fieldId'  => $fieldId,
				'parentId' => $parentId
			));

			if ($childIds)
			{
				$values = array();

				foreach ($childIds as $sortOrder => $childId)
				{
					$values[] = array($fieldId, $parentId, $childId, $sortOrder+1);
				}

				$columns = array('fieldId', 'parentId', 'childId', 'sortOrder');
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
