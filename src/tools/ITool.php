<?php
namespace Craft;

/**
 * Interface ITool
 *
 * @package craft.app.tools
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

	/**
	 * @param array $params
	 * @return array
	 */
	public function performAction($params = array());
}
