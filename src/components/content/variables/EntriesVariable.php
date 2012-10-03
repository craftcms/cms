<?php
namespace Blocks;

/**
 * Content functions
 */
class EntriesVariable
{
	/**
	 * Gets the total number of entries.
	 *
	 * @param array|null $params
	 * @return array
	 */
	public function totalEntries($params = array())
	{
		$params = new EntryParams($params);
		return blx()->entries->getTotalEntries($params);
	}

	/**
	 * Gets entries.
	 *
	 * @param array|null $params
	 * @return array
	 */
	public function entries($params = array())
	{
		$params = new EntryParams($params);
		$entries = blx()->entries->getEntries($params);
		return EntryVariable::populateVariables($entries);
	}

	/**
	 * Gets an entry.
	 *
	 * @param array|null $params
	 * @return EntryVariable|null
	 */
	public function entry($params = array())
	{
		$params = new EntryParams($params);
		$entry = blx()->entries->getEntry($params);

		if ($entry)
		{
			return new EntryVariable($entry);
		}
	}
}
