<?php
namespace Blocks;

/**
 * Link type functions
 */
class LinksVariable
{
	/**
	 * Returns all installed block types.
	 *
	 * @return array
	 */
	public function getAllLinkTypes()
	{
		$linkTypes = blx()->links->getAllLinkTypes();
		return LinkTypeVariable::populateVariables($linkTypes);
	}

	/**
	 * Gets a block type.
	 *
	 * @param string $class
	 * @return LinkTypeVariable|null
	 */
	public function getLinkType($class)
	{
		$linkType = blx()->links->getLinkType($class);

		if ($linkType)
		{
			return new LinkTypeVariable($linkType);
		}
	}

	/**
	 * Populates a block type.
	 *
	 * @param BaseBlockModel $block
	 * @return BaseLinkType|null
	 */
	public function populateLinkType(BaseBlockModel $block)
	{
		$linkType = blx()->links->populateLinkType($block);
		if ($linkType)
		{
			return new LinkTypeVariable($linkType);
		}
	}
}
