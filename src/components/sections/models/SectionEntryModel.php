<?php
namespace Blocks;

/**
 *
 */
class SectionEntryModel extends EntryModel
{
	protected $entryType = 'SectionEntry';

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'sectionId' => AttributeType::Number,
			'authorId'  => AttributeType::Number,
			'slug'      => AttributeType::String,
		));
	}

	/**
	 * Returns the entry's section.
	 *
	 * @return SectionModel|null
	 */
	public function getSection()
	{
		if ($this->sectionId)
		{
			return blx()->sections->getSectionById($this->sectionId);
		}
	}
}
