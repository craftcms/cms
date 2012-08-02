<?php
namespace Blocks;

/**
 *
 */
class SettingsService extends \CApplicationComponent
{
	/**
	 * @param        $table
	 * @param        $settings
	 * @param string $category
	 * @param bool   $deletePrevious
	 * @return bool
	 */
	public function saveSettings($table, $settings, $category = null, $deletePrevious = false)
	{
		if ($deletePrevious)
			$this->deleteSettings($table, $category);

		if ($settings)
		{
			$flattened = ArrayHelper::flattenArray($settings);

			$settingsPrep = array();
			foreach ($flattened as $key => $value)
				$settingsPrep[] = $category !== null ? array($key, $value, $category) : array($key, $value);

			if ($category !== null)
				$result = blx()->db->createCommand()->insertAll($table, array('name', 'value', 'category'), $settingsPrep);
			else
				$result = blx()->db->createCommand()->insertAll($table, array('name', 'value'), $settingsPrep);
		}
		else
			$result = true;

		return $result;
	}

	/**
	 * @param       $table
	 * @param null  $category
	 * @param array $names
	 * @return bool
	 */
	public function deleteSettings($table, $category = null, $names = array())
	{
		$result = false;

		if (!empty($names) && $category == null)
			$result = blx()->db->createCommand()->delete($table, array('in', 'name', $names));
		elseif (empty($names) && $category !== null)
			$result = blx()->db->createCommand()->delete($table, 'category = :category', array(':category' => $category));
		elseif (!empty($names) && $category !== null)
			$result = blx()->db->createCommand()->delete($table, array('and', array('in', 'name', $names), 'category = :category', array(':category' => $category)));

		if ($result === false)
			return false;

		return $result;
	}

	/**
	 * @param string $category
	 * @return array
	 */
	public function getSystemSettings($category)
	{
		$settings = SystemSetting::model()->findAllByAttributes(array(
			'category' => $category
		));

		$settings = ArrayHelper::expandSettingsArray($settings);

		return $settings;
	}
}
