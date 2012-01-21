<?php

/**
 *
 */
class bSettingsService extends CApplicationComponent
{
	/**
	 * @param        $table
	 * @param        $settings
	 * @param string $prefix
	 * @param string $category
	 * @param bool   $deletePrevious
	 * @return bool
	 */
	public function saveSettings($table, $settings, $prefix = '', $category = null, $deletePrevious = false)
	{
		$flattened = bArrayHelper::flattenArray($settings, $prefix);

		if ($deletePrevious)
			$this->deleteSettings($table, array_keys($flattened), $category);

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
	 * @param array $keys
	 * @param null  $category
	 * @return bool
	 */
	public function deleteSettings($table, $keys = array(), $category = null)
	{
		$result = false;

		if (!empty($keys) && $category == null)
			$result = Blocks::app()->db->delete('{{'.$table.'}}', array('in', 'key', $keys));
		elseif (empty($keys) && $category !== null)
			$result = Blocks::app()->db->delete('{{'.$table.'}}', 'category = '.$category);
		elseif (!empty($keys) && $category !== null)
			$result = Blocks::app()->db->delete('{{'.$table.'}}', array('and', array('in', 'key', $keys), 'category = '.$category));

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
			$systemSettings = bSystemSetting::model()->findAll();
		else
			$systemSettings = bSystemSetting::model()->findAllByAttributes(array(
				'category' => $category
			));

		return $systemSettings;
	}
}
