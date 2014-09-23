<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140922_000001_delete_orphaned_matrix_blocks extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Get the IDs of any orphaned Matrix block rows in craft_elements
		$ids = craft()->db->createCommand()
			->select('e.id')
			->from('elements e')
			->leftJoin('matrixblocks m', 'm.id = e.id')
			->where(
				array('and', 'e.type = :type', 'm.id is null'),
				array(':type' => ElementType::MatrixBlock)
			)
			->queryColumn();

		if ($ids)
		{
			// Delete 'em
			$this->delete('elements', array('in', 'id', $ids));
		}

		return true;
	}
}
