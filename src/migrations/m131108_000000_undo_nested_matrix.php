<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m131108_000000_undo_nested_matrix extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Find any nested Matrix fields
		$nestedMatrixFields = craft()->db->createCommand()
			->select('id, handle, context')
			->from('fields')
			->where(array('and',
				'type = "Matrix"',
				array('like', 'context', 'matrixBlockType:%')
			))
			->queryAll();

		if ($nestedMatrixFields)
		{
			$fieldIds = array();

			foreach ($nestedMatrixFields as $matrixField)
			{
				$blockTypeId = substr($matrixField['context'], 16);

				$parentFieldHandle = craft()->db->createCommand()
					->select('fields.handle')
					->from('fields fields')
					->join('matrixblocktypes matrixblocktypes', 'matrixblocktypes.fieldId = fields.id')
					->where('matrixblocktypes.id = :id', array(':id' => $blockTypeId))
					->queryScalar();

				if ($parentFieldHandle)
				{
					$contentTable = 'matrixcontent_'.strtolower($parentFieldHandle.'_'.$matrixField['handle']);
					$this->dropTableIfExists($contentTable);
				}

				$fieldIds[] = $matrixField['id'];
			}

			$this->delete('fields', array('in', 'id', $fieldIds));
		}

		return true;
	}
}
