<?php
namespace Blocks;

/**
 * Link type base class
 */
abstract class BaseLinkType extends BaseComponent
{
	/**
	 * The type of component this is.
	 *
	 * @access protected
	 * @var string
	 */
	protected $componentType = 'LinkType';

	/**
	 * Returns the name of the table where entities are stored.
	 *
	 * @abstract
	 * @return string
	 */
	abstract public function getEntityTableName();

	/**
	 * Mass populates entity models.
	 *
	 * @abstract
	 * @param array $data
	 * @return array
	 */
	abstract public function populateEntities($data);

	/**
	 * Defines any link type-specific settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array();
	}

	/**
	 * Modifies the DbCommand being created that's used to retrieve the linked entities.
	 *
	 * @param DbCommand $query
	 * @return DbCommand
	 */
	public function modifyLinkedEntitiesQuery($query)
	{
		return $query;
	}

	/**
	 * Returns the linkable entity models.
	 *
	 * @abstract
	 * @param array $settings
	 * @return array
	 */
	abstract public function getLinkableEntities($settings);
}
