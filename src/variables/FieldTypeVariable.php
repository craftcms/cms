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

	/**
	 * Returns static HTML for the field's value.
	 *
	 * @param string $value
	 * @return string
	 */
	public function getStaticHtml($value)
	{
		return $this->component->getStaticHtml($value);
	}
}
