<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140731_000001_resave_elements_with_assets_in_temp_sources extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Get all Assets fields
		$fields = craft()->db->createCommand()
			->select('fields.id, fields.settings')
			->from('fields fields')
			->where('fields.type = "Assets"')
			->queryAll();

		$affectedFields = array();

		// Select those, that have a dynamic default upload location set.
		foreach ($fields as $field)
		{
			$settings = JsonHelper::decode($field['settings']);

			if (
				empty($settings['useSingleFolder'])
				&& !empty($settings['defaultUploadLocationSubpath'])
				&& strpos($settings['defaultUploadLocationSubpath'], '{') !== false)
			{
				$affectedFields[] = $field;
			}
		}

		$affectedElements = array();

		// Get the element ids, that have Assets linked to them via affected fields that still reside in a temporary source.
		if (!empty($affectedFields))
		{
			foreach ($affectedFields as $field)
			{
				$data = $this->_getAffectedElements($field);

				foreach ($data as $row)
				{
					$affectedElements[$row['type']][] = $row['elementId'];
				}
			}
		}

		foreach ($affectedElements as $elementType => $ids)
		{
			$criteria = craft()->elements->getCriteria($elementType);
			$criteria->status = null;
			$criteria->limit = null;
			$criteria->id = $ids;

			craft()->tasks->createTask('ResaveElements', Craft::t('Resaving {element} elements affected by Assets bug', array('element' => $elementType)), array(
				'elementType' => $elementType,
				'criteria'    => $criteria->getAttributes()
			));
		}



		return true;
	}

	/**
	 * Get affected element Ids for a field.
	 *
	 * @param $field
	 *
	 * @return array|\CDbDataReader
	 */
	private function _getAffectedElements($field)
	{
		return craft()->db->createCommand()
			->select('DISTINCT (relations.sourceId) AS elementId, elements.type')
			->from('relations relations')
			->join('assetfiles assetfiles', 'assetfiles.id = relations.targetId')
			->join('elements elements', 'relations.sourceId = elements.id')
			->where('relations.fieldId = :fieldId', array(':fieldId' => $field['id']))
			->andWhere('assetfiles.sourceId is null')
			->queryAll();
	}
}
