<?php
namespace Blocks;

/**
 * Singleton entry type
 */
class SingletonEntryType extends BaseEntryType
{
	/**
	 * Returns the entry type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Singletons');
	}

	/**
	 * Returns the CP edit URI for a given entry.
	 *
	 * @param EntryModel $entry
	 * @return string|null
	 */
	public function getCpEditUriForEntry(EntryModel $entry)
	{
		return 'content/singletons/'.$entry->id;
	}

	/**
	 * Returns the site template path for a matched entry.
	 *
	 * @param SingletonModel
	 * @return string|false
	 */
	public function getSiteTemplateForMatchedEntry(SingletonModel $entry)
	{
		return $entry->template;
	}

	/**
	 * Returns the variable name the matched entry should be assigned to.
	 *
	 * @return string
	 */
	public function getVariableNameForMatchedEntry()
	{
		return 'singleton';
	}

	/**
	 * Returns whether this entry type is linkable.
	 *
	 * @return bool
	 */
	public function isLinkable()
	{
		return true;
	}

	/**
	 * Defines any custom entry criteria attributes for this entry type.
	 *
	 * @return array
	 */
	public function defineCustomCriteriaAttributes()
	{
		return array(
			'name' => AttributeType::String,
		);
	}

	/**
	 * Modifies an entries query targeting entries of this type.
	 *
	 * @param DbCommand $query
	 * @param EntryCriteriaModel $criteria
	 * @return mixed
	 */
	public function modifyEntriesQuery(DbCommand $query, EntryCriteriaModel $criteria)
	{
		$query
			->addSelect('s.name, s.template, s.fieldLayoutId')
			->join('singletons s', 's.id = e.id');
	}

	/**
	 * Populates an entry model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateEntryModel($row)
	{
		return SingletonModel::populateModel($row);
	}
}
