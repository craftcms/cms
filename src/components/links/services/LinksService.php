<?php
namespace Blocks;

/**
 *
 */
class LinksService extends BaseApplicationComponent
{
	/**
	 * Returns all installed link types.
	 *
	 * @return array
	 */
	public function getAllLinkTypes()
	{
		return blx()->components->getComponentsByType('link');
	}

	/**
	 * Gets a link type.
	 *
	 * @param string $class
	 * @return BaseLinkType|null
	 */
	public function getLinkType($class)
	{
		return blx()->components->getComponentByTypeAndClass('link', $class);
	}

	/**
	 * Populates a link type.
	 *
	 * @param LinkModel $link
	 * @return BaseLinkType|null
	 */
	/*public function populateLinkType(LinkModel $link)
	{
		return blx()->components->populateComponentByTypeAndModel('link', $link);
	}*/

	/**
	 * Populates an asset source model.
	 *
	 * @param array|LinkRecord $attributes
	 * @return LinkModel
	 */
	/*public function populateLink($attributes)
	{
		if ($attributes instanceof LinkRecord)
		{
			$attributes = $attributes->getAttributes();
		}

		$link = new LinkModel();

		$link->id = $attributes['id'];
		$link->name = $attributes['name'];
		$link->type = $attributes['type'];
		$link->settings = $attributes['settings'];

		return $link;
	}*/

	/**
	 * Mass-populates asset source model.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	/*public function populateLinks($data, $index = 'id')
	{
		$links = array();

		foreach ($data as $attributes)
		{
			$link = $this->populateLink($attributes);
			$links[$link->$index] = $link;
		}

		return $links;
	}*/

	/**
	 * Gets the linked entities for a Links block.
	 *
	 * @param BaseBlockModel $block
	 * @param int $parentId
	 */
	public function getLinkedEntities(BaseBlockModel $block, $parentId)
	{
		$linkType = $this->getLinkType($block->settings['type']);
		if (!$linkType)
		{
			throw new Exception(Blocks::t('No link type exists with the class “{class}”', array('class' => $block->settings['type'])));
		}

		$table = $linkType->getEntityTableName();
		$query = blx()->db->createCommand()
			->select($table.'.*')
			->from($table.' '.$table)
			->join('links l', 'l.childId = '.$table.'.id')
			->where(array(
				'l.blockId' => $block->id,
				'l.parentType' => $block->getEntityType(),
				'l.parentId' => $parentId
			));

		// Give the link type a chance to make any changes
		$query = $linkType->modifyLinkedEntitiesQuery($query);

		$rows = $query->queryAll();
		return $linkType->populateEntities($rows);
	}
}
