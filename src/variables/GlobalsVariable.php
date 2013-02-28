<?php
namespace Craft;

/**
 * Globals functions
 */
class GlobalsVariable
{
	/**
	 * Gets all global sets.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllSets($indexBy = null)
	{
		return craft()->globals->getAllSets($indexBy);
	}

	/**
	 * Gets all global sets that are editable by the current user.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getEditableSets($indexBy = null)
	{
		return craft()->globals->getEditableSets($indexBy);
	}

	/**
	 * Gets the total number of global sets.
	 *
	 * @return int
	 */
	public function getTotalSets()
	{
		return craft()->globals->getTotalSets();
	}

	/**
	 * Gets the total number of global sets that are editable by the current user.
	 *
	 * @return int
	 */
	public function getTotalEditableSets()
	{
		return craft()->globals->getTotalEditableSets();
	}

	/**
	 * Gets a global set by its ID.
	 *
	 * @param int $id
	 * @return GlobalSetModel|null
	 */
	public function getSetById($id)
	{
		return craft()->globals->getSetById($id);
	}
}
