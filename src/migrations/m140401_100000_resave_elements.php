<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140401_100000_resave_elements extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// No need to torture the Dev track guys again
		if (craft()->migrations->hasRun('m140204_000012_resave_elements'))
		{
			return true;
		}

		Craft::log('Making sure everyone has a row in elements_18n, first.', LogLevel::Info, true);

		// Find all of the elements that don't have a row in this table yet
		$elementIds = craft()->db->createCommand()
				->select('elements.id')
				->from('elements elements')
				->leftJoin('elements_i18n elements_i18n', 'elements_i18n.elementId = elements.id')
				->where('elements_i18n.id IS NULL')
				->queryColumn();

		if ($elementIds)
		{
			Craft::log('Found '.count($elementIds).' elements that need a row in elements_i18n.  Adding...', LogLevel::Info, true);
			$locale = craft()->i18n->getPrimarySiteLocaleId();

			foreach ($elementIds as $elementId)
			{
				craft()->config->maxPowerCaptain();

				$this->insert('elements_i18n', array(
						'elementId' => $elementId,
						'locale'    => $locale
				));
			}

			Craft::log('Done inserting into elements_i18n.', LogLevel::Info, true);
		}

		// Queue up a Resave All Elements task
		craft()->tasks->createTask('ResaveAllElements');

		return true;
	}
}
