<?php
namespace Craft;

/**
 * Relation field data class
 */
class RelationFieldData extends \ArrayObject
{
	public $all;

	/**
	 * Constructor
	 *
	 * @param array|null $values
	 */
	function __construct($values = null)
	{
		if (is_array($values))
		{
			$this->all = $values;
		}
		else
		{
			$this->all = array();
		}

		// Only the enabled/live elements make it to the primary $values array
		$enabledElements = array();

		foreach ($this->all as $element)
		{
			if ($element->getStatus() == 'enabled' || $element->getStatus() == 'live')
			{
				$enabledElements[] = $element;
			}
		}

		parent::__construct($enabledElements);
	}
}
