<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m150724_000001_adjust_quality_settings extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Adjusting Asset transform quality settings', LogLevel::Info, true);

		$transforms = craft()->db->createCommand()
			->select('id, quality')
			->from('assettransforms')
			->queryAll();

		foreach ($transforms as $transform)
		{
			$quality = $transform['quality'];

			if (!$quality)
			{
				continue;
			}

			$closest = 0;
			$closestDistance = 100;
			$qualityLevels = array(10, 30, 60, 82, 100);

			foreach ($qualityLevels as $qualityLevel)
			{
				if (abs($quality - $qualityLevel) <= $closestDistance)
				{
					$closest = $qualityLevel;
					$closestDistance = abs($quality - $qualityLevel);
				}
			}

			craft()->db->createCommand()
				->update('assettransforms', array('quality' => $closest), 'id = :id', array(':id' => $transform['id']));
		}

		Craft::log('Done adjusting Asset transform quality settings', LogLevel::Info, true);

		return true;
	}
}
