<?php
namespace Craft;

/**
 * Tool interface
 */
interface ITool extends IComponentType
{
	/**
	 * @return string
	 */
	public function getIconValue();

	/**
	 * @return string
	 */
	public function getOptionsHtml();

	/**
	 * @return string
	 */
	public function getButtonLabel();
}
