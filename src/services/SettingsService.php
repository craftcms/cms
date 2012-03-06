<?php
namespace Blocks;

/**
 *
 */
class SettingsService extends BaseService
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
			$result = b()->db->createCommand()->insertAll('{{'.$table.'}}', array('name', 'value', 'category'), $settingsPrep);
		else
			$result = b()->db->createCommand()->insertAll('{{'.$table.'}}', array('name', 'value'), $settingsPrep);

		if ($result === false)
			return false;

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
			$result = b()->db->createCommand()->delete('{{'.$table.'}}', array('in', 'name', $names));
		elseif (empty($names) && $category !== null)
			$result = b()->db->createCommand()->delete('{{'.$table.'}}', 'category = :category', array(':category' => $category));
		elseif (!empty($names) && $category !== null)
			$result = b()->db->createCommand()->delete('{{'.$table.'}}', array('and', array('in', 'name', $names), 'category = :category', array(':category' => $category)));

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
