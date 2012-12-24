<?php
namespace Blocks;

/**
 * Asset link type class
 */
class AssetLinkType extends BaseLinkType
{
	/**
	 * Returns the type of links this creates.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Assets');
	}

	/**
	 * Returns the name of the table where entities are stored.
	 *
	 * @return string
	 */
	public function getEntityTableName()
	{
		return 'assetfiles';
	}

	/**
	 * Defines any link type-specific settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			// Maps to AssetParams->sourceId
			'sourceId' => AttributeType::Mixed,
		);
	}

	/**
	 * Returns the link's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return blx()->templates->render('_components/linktypes/Assets/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Mass populates entity models.
	 *
	 * @param array $data
	 * @return array
	 */
	public function populateEntities($data)
	{
		return AssetFileModel::populateModels($data);
	}

	/**
	 * Returns the linkable entity models.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function getLinkableEntities($settings)
	{
		$criteria = new FileCriteria($settings);
		return blx()->assets->findFiles($criteria);
	}
}
