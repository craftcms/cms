<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140204_000005_resave_elements extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Resaving all localizable elements', LogLevel::Info, true);

		foreach (craft()->elements->getAllElementTypes() as $elementType)
		{
			if ($elementType->isLocalized())
			{
				$criteria = craft()->elements->getCriteria($elementType->getClassHandle());
				craft()->elements->resaveElements($criteria);
			}
		}

		return true;
	}
}
