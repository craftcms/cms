<?php
namespace Craft;

/**
 * Class EntriesFieldType
 *
 * @package craft.app.fieldtypes
 */
class EntriesFieldType extends BaseElementFieldType
{
	/**
	 * @var string $elementType The element type this field deals with.
	 */
	protected $elementType = 'Entry';

	/**
	 * Returns the label for the "Add" button.
	 *
	 * @return string
	 */
	protected function getAddButtonLabel()
	{
		return Craft::t('Add an entry');
	}
}
