<?php
namespace Blocks;

/**
 * Entry type base class
 */
abstract class BaseEntryType extends BaseComponentType
{
	/**
	 * @access protected
	 * @var string The type of component this is
	 */
	protected $componentType = 'EntryType';

	/**
	 * @access private
	 * @var BaseModel The model representing the current component instance's link settings.
	 */
	private $_linkSettings;

	/**
	 * Returns the CP edit URI for a given entry.
	 *
	 * @param EntryModel $entry
	 * @return string|false
	 */
	public function getCpEditUriForEntry(EntryModel $entry)
	{
		return false;
	}

	/**
	 * Returns the site template path for a matched entry.
	 *
	 * @param EntryModel
	 * @return string|false
	 */
	public function getSiteTemplateForMatchedEntry(EntryModel $entry)
	{
		return false;
	}

	/**
	 * Returns the variable name the matched entry should be assigned to.
	 *
	 * @return string
	 */
	public function getVariableNameForMatchedEntry()
	{
		return 'entry';
	}

	/**
	 * Returns whether this entry type is localizable.
	 *
	 * @return bool
	 */
	public function isLocalizable()
	{
		return false;
	}

	/**
	 * Returns whether this entry type is linkable.
	 *
	 * @return bool
	 */
	public function isLinkable()
	{
		return false;
	}

	/**
	 * Defines any custom entry criteria attributes for this entry type.
	 *
	 * @return array
	 */
	public function defineCustomCriteriaAttributes()
	{
		return array();
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
	}

	/**
	 * Populates an entry model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateEntryModel($row)
	{
		return EntryModel::populateModel($row);
	}

	/**
	 * Gets the link settings.
	 *
	 * @return BaseModel
	 */
	public function getLinkSettings()
	{
		if (!isset($this->_linkSettings))
		{
			$this->_linkSettings = $this->getLinkSettingsModel();
		}

		return $this->_linkSettings;
	}

	/**
	 * Sets the link setting values.
	 *
	 * @param array $values
	 */
	public function setLinkSettings($values)
	{
		if ($values)
		{
			$this->getLinkSettings()->setAttributes($values);
		}
	}

	/**
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function prepLinkSettings($settings)
	{
		return $settings;
	}

	/**
	 * Returns the link settings HTML.
	 *
	 * @return string|null
	 */
	public function getLinkSettingsHtml()
	{
		return null;
	}

	/**
	 * Gets the link settings model.
	 *
	 * @access protected
	 * @return BaseModel
	 */
	protected function getLinkSettingsModel()
	{
		return new Model($this->defineCustomCriteriaAttributes());
	}
}
