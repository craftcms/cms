<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140325_000000_fix_matrix_settings extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$matrixFields = craft()->db->createCommand()
			->select('id, settings')
			->from('fields')
			->where('type = "Matrix"')
			->queryAll();

		foreach ($matrixFields as $field)
		{
			$settings = JsonHelper::decode($field['settings']);

			if (isset($settings['__model__']))
			{
				unset($settings['__model__']);

				$this->update('fields', array(
					'settings' => JsonHelper::encode($settings)
				), array(
					'id' => $field['id']
				));
			}
		}

		return true;
	}
}
