<?php
namespace Craft;

/**
 * Class UsersFieldType
 *
 * @package craft.app.fieldtypes
 */
class UsersFieldType extends BaseElementFieldType
{
	/**
	 * @var string $elementType The element type this field deals with.
	 */
	protected $elementType = 'User';

	/**
	 * Returns the label for the "Add" button.
	 *
	 * @return string
	 */
	protected function getAddButtonLabel()
	{
		return Craft::t('Add a user');
	}
}
