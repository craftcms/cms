<?php
namespace Craft;

/**
 * Field type template variable
 */
class FieldTypeVariable extends BaseComponentTypeVariable
{
	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $handle
	 * @param mixed $value
	 * @return string
	 */
	public function getInputHtml($handle, $value)
	{
		return $this->component->getInputHtml($handle, $value);
	}
}
