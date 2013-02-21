<?php
namespace Blocks;

/**
 * Globals entry type
 */
class GlobalsEntryType extends BaseEntryType
{
	/**
	 * Returns the entry type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Globals');
	}

	/**
	 * Returns the CP edit URI for a given entry.
	 *
	 * @param EntryModel $entry
	 * @return string|null
	 */
	public function getCpEditUriForEntry(EntryModel $entry)
	{
		return 'content/globals';
	}
}
