<?php
namespace Craft;

/**
 * Search Index tool
 */
class SearchIndexTool extends BaseTool
{
	/**
	 * Returns the tool name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Update Search Indexes');
	}
}
