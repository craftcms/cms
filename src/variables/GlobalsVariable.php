<?php
namespace Craft;

/**
 * Globals functions
 */
class GlobalsVariable
{
	/**
	 * Returns all global sets.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllSets($indexBy = null)
	{
		return craft()->globals->getAllSets($indexBy);
	}

	/**
	 * Returns all global sets that are editable by the current user.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getEditableSets($indexBy = null, $localeId = null)
	{
		return craft()->globals->getEditableSets($indexBy, $localeId);
	}

	/**
	 * Returns the total number of global sets.
	 *
	 * @return int
	 */
	public function getTotalSets()
	{
		return craft()->globals->getTotalSets();
	}

	/**
	 * Returns the total number of global sets that are editable by the current user.
	 *
	 * @return int
	 */
	public function getTotalEditableSets()
	{
		return craft()->globals->getTotalEditableSets();
	}

	/**
	 * Returns a global set by its ID.
	 *
	 * @param int $id
	 * @return GlobalSetModel|null
	 */
	public function getSetById($id)
	{
		return craft()->globals->getSetById($id);
	}
}
