<?php
namespace Blocks;

/**
 * Asset entry type
 */
class AssetEntryType extends BaseEntryType
{
	/**
	 * Returns the entry type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Assets');
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
			'sourceId' => AttributeType::Number,
			'folderId' => AttributeType::Number,
			'filename' => AttributeType::String,
			'kind'     => AttributeType::String,
			'order'    => array(AttributeType::String, 'default' => 'filename asc'),
		);
	}

	/**
	 * Returns the link settings HTML
	 *
	 * @return string|null
	 */
	public function getLinkSettingsHtml()
	{
		return blx()->templates->render('_components/entrytypes/Asset/linksettings', array(
			'settings' => $this->getLinkSettings()
		));
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
			->addSelect('f.sourceId, f.folderId, f.filename, f.kind, f.width, f.height, f.size, f.dateModified')
			->join('assetfiles f', 'f.id = e.id');
	}

	/**
	 * Populates an entry model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateEntryModel($row)
	{
		return AssetFileModel::populateModel($row);
	}
}
