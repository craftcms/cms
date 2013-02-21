<?php
namespace Blocks;

/**
 * Link functions
 */
class LinksVariable
{
	/**
	 * Returns all linkable entry types.
	 *
	 * @return array
	 */
	public function getAllLinkableEntryTypes()
	{
		$entryTypes = blx()->links->getAllLinkableEntryTypes();
		return EntryTypeVariable::populateVariables($entryTypes);
	}

	/**
	 * Returns a linkable entry type.
	 *
	 * @param string $class
	 * @return EntryTypeVariable|null
	 */
	public function getLinkableEntryType($class)
	{
		$entryType = blx()->links->getLinkableEntryType($class);

		if ($entryType)
		{
			return new EntryTypeVariable($entryType);
		}
	}
}
