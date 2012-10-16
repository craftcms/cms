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
	 * @param BaseBlockEntityModel $entity
	 */
	public function getLinkedEntities(BaseBlockModel $block, BaseBlockEntityModel $entity)
	{
		$linkType = $this->_getLinkType($block->settings['type']);

		$table = $linkType->getEntityTableName();
		$query = blx()->db->createCommand()
			->select($table.'.*')
			->from($table.' '.$table)
			->join('links l', 'l.childId = '.$table.'.id')
			->where(array(
				'l.parentType' => $entity->getClassHandle(),
				'l.blockId' => $block->id,
				'l.parentId' => $entity->id
			))
			->order('l.sortOrder');

		// Give the link type a chance to make any changes
		$query = $linkType->modifyLinkedEntitiesQuery($query);

		$rows = $query->queryAll();
		return $linkType->populateEntities($rows);
	}

	/**
	 * Sets the linked entities for a Links block.
	 *
	 * @param BaseBlockModel       $block
	 * @param BaseBlockEntityModel $entity
	 * @throws \Exception
	 * @return void
	 */
	public function setLinks(BaseBlockModel $block, BaseBlockEntityModel $entity)
	{
		$linkType = $this->_getLinkType($block->settings['type']);

		$transaction = blx()->db->beginTransaction();
		try
		{
			// Delete the existing links
			blx()->db->createCommand()->delete('links', array(
				'parentType' => $entity->getClassHandle(),
				'blockId' => $block->id,
				'parentId' => $entity->id
			));

			// Save the new ones
			$blockHandle = $block->handle;
			$childIds = $entity->getRawValue($blockHandle);

			if ($childIds)
			{
				foreach ($childIds as $sortOrder => $childId)
				{
					$values[] = array(
						$entity->getClassHandle(),
						$block->id,
						$entity->id,
						$linkType->getClassHandle(),
						$childId,
						($sortOrder+1)
					);
				}

				$columns = array('parentType', 'blockId', 'parentId', 'childType', 'childId', 'sortOrder');
				blx()->db->createCommand()->insertAll('links', $columns, $values);
			}

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Returns a link type instance.
	 *
	 * @param string $type
	 * @return BaseLinkType
	 * @throws Exception
	 */
	private function _getLinkType($type)
	{
		$linkType = $this->getLinkType($type);

		if ($linkType)
		{
			return $linkType;
		}
		else
		{
			throw new Exception(Blocks::t('No link type exists with the class “{class}”', array('class' => $type)));
		}
	}
}
