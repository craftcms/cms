<?php
namespace Craft;

/**
 *
 */
class StructuredEntryRecord extends EntryRecord
{
	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'nestedSetBehavior' => 'app.extensions.NestedSetBehavior',
		);
	}
}
