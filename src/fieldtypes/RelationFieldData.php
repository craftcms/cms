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
			// TODO: Elements really need a single way of identifying whether they should be "live" or not.
			if (in_array($element->getStatus(), array('enabled', 'live', 'active')))
			{
				$enabledElements[] = $element;
			}
		}

		parent::__construct($enabledElements);
	}
}
