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
	 * @param string $prefix
	 * @param string $category
	 * @param bool   $deletePrevious
	 * @return bool
	 */
	public function saveSettings($table, $settings, $prefix = null, $category = null, $deletePrevious = false)
	{
		$flattened = ArrayHelper::flattenArray($settings, $prefix);

		if ($deletePrevious)
			$this->deleteSettings($table, $category);

		$settingsPrep = array();
		foreach ($flattened as $key => $value)
			$settingsPrep[] = $category !== null ? array($key, $value, $category) : array($key, $value);

		if ($category !== null)
			$result = Blocks::app()->db->createCommand()->insertAll('{{'.$table.'}}', array('key', 'value', 'category'), $settingsPrep);
		else
			$result = Blocks::app()->db->createCommand()->insertAll('{{'.$table.'}}', array('key', 'value'), $settingsPrep);

		if ($result === false)
			return false;

		return $result;
	}

	/**
	 * @param       $table
	 * @param null  $category
	 * @param array $keys
	 * @return bool
	 */
	public function deleteSettings($table, $category = null, $keys = array())
	{
		$result = false;

		if (!empty($keys) && $category == null)
			$result = Blocks::app()->db->createCommand()->delete('{{'.$table.'}}', array('in', 'key', $keys));
		elseif (empty($keys) && $category !== null)
			$result = Blocks::app()->db->createCommand()->delete('{{'.$table.'}}', 'category = :category', array(':category' => $category));
		elseif (!empty($keys) && $category !== null)
			$result = Blocks::app()->db->createCommand()->delete('{{'.$table.'}}', array('and', array('in', 'key', $keys), 'category = :category', array(':category' => $category)));

		if ($result === false)
			return false;

		return $result;
	}

	/**
	 * @param string $category
	 * @return mixed
	 */
	public function getSystemSettings($category = null)
	{
		if ($category == null)
			$systemSettings = SystemSetting::model()->findAll();
		else
			$systemSettings = SystemSetting::model()->findAllByAttributes(array(
				'category' => $category
			));

		return $systemSettings;
	}
}
